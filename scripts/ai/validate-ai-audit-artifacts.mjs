import fs from 'node:fs';
import path from 'node:path';

const [sourceArg, goldArg, evalArg] = process.argv.slice(2);

if (!sourceArg || !goldArg || !evalArg) {
    console.error('Usage: node scripts/ai/validate-ai-audit-artifacts.mjs <source.json> <gold.json> <eval.json>');
    process.exit(1);
}

const readJson = (file) => JSON.parse(fs.readFileSync(path.resolve(file), 'utf8'));
const source = readJson(sourceArg);
const gold = readJson(goldArg);
const evals = readJson(evalArg);
const errors = [];
const warnings = [];

const same = (left, right) => JSON.stringify(left) === JSON.stringify(right);
const sortedNumbers = (values) => [...values].map(Number).sort((a, b) => a - b);
const sourceIdeas = new Map(source.ideas.map((idea) => [idea.id, idea]));
const sourceCategories = new Map(source.taxonomy.categories.map((category) => [category.id, category]));
const sourceTags = new Set(source.taxonomy.tags.map((tag) => tag.id));
const goldIdeas = new Map(gold.ideas.map((idea) => [idea.id, idea]));

if (gold.schema_version !== 'ai-gold-standard-v1') errors.push('Unexpected Gold Standard schema_version.');
if (evals.schema_version !== 'ai-eval-cases-v1') errors.push('Unexpected eval schema_version.');
if (gold.ideas.length !== source.ideas.length) errors.push('Gold Standard idea count differs from source.');
if (goldIdeas.size !== gold.ideas.length) errors.push('Gold Standard contains duplicate idea IDs.');

for (const sourceIdea of source.ideas) {
    const audited = goldIdeas.get(sourceIdea.id);
    if (!audited) {
        errors.push(`Missing Gold Standard idea ${sourceIdea.id}.`);
        continue;
    }

    const current = audited.current;
    const recommended = audited.recommended;
    const expectedClassifications = Object.fromEntries(
        [...new Set(sourceIdea.classifications.map((item) => String(item.dimension_id)))]
            .map((dimensionId) => [dimensionId, sourceIdea.classifications.filter((item) => String(item.dimension_id) === dimensionId).map((item) => item.category_id)])
    );

    if (current.title !== sourceIdea.content.title) errors.push(`Idea ${sourceIdea.id}: current title differs from source.`);
    if ((current.problem_opportunity ?? null) !== (sourceIdea.content.problem_opportunity || null)) errors.push(`Idea ${sourceIdea.id}: current problem differs from source.`);
    if (current.primary_category_id !== sourceIdea.primary_category.id) errors.push(`Idea ${sourceIdea.id}: current primary category differs from source.`);
    if (!same(current.classification_category_ids, expectedClassifications)) errors.push(`Idea ${sourceIdea.id}: current classifications differ from source.`);
    if (!same(current.tag_ids, sourceIdea.tags.map((tag) => tag.id))) errors.push(`Idea ${sourceIdea.id}: current tags differ from source.`);
    if ((current.parent_idea_id ?? null) !== (sourceIdea.hierarchy.parent_idea_id ?? null)) errors.push(`Idea ${sourceIdea.id}: current parent differs from source.`);

    if (!['draft', 'private'].includes(recommended.completeness?.recommendation)) errors.push(`Idea ${sourceIdea.id}: invalid completeness recommendation.`);
    if (!Array.isArray(recommended.completeness?.missing_information)) errors.push(`Idea ${sourceIdea.id}: missing_information must be an array.`);

    const totalTags = (recommended.tag_ids?.length ?? 0) + (recommended.new_tags?.length ?? 0);
    if ((recommended.new_tags?.length ?? 0) > 2) errors.push(`Idea ${sourceIdea.id}: more than two new tags.`);
    if (recommended.completeness.recommendation === 'private' && (totalTags < 4 || totalTags > 7)) errors.push(`Idea ${sourceIdea.id}: complete idea must have 4-7 tags.`);
    if (recommended.completeness.recommendation === 'draft' && totalTags > 7) errors.push(`Idea ${sourceIdea.id}: draft has more than seven tags.`);
    for (const tagId of recommended.tag_ids ?? []) if (!sourceTags.has(tagId)) errors.push(`Idea ${sourceIdea.id}: unknown recommended tag ${tagId}.`);

    if (recommended.primary_category_id != null) {
        const category = sourceCategories.get(recommended.primary_category_id);
        if (!category) errors.push(`Idea ${sourceIdea.id}: unknown recommended primary category.`);
        if (category && !category.is_active && !recommended.primary_category_requires_taxonomy_change) errors.push(`Idea ${sourceIdea.id}: inactive category without taxonomy-change marker.`);
        const areaIds = recommended.classification_category_ids?.['1'] ?? [];
        if (!areaIds.includes(recommended.primary_category_id)) errors.push(`Idea ${sourceIdea.id}: primary category is absent from dimension 1.`);
    }

    for (const [dimensionId, categoryIds] of Object.entries(recommended.classification_category_ids ?? {})) {
        for (const categoryId of categoryIds) {
            const category = sourceCategories.get(categoryId);
            if (!category) errors.push(`Idea ${sourceIdea.id}: unknown classification category ${categoryId}.`);
            if (category && String(category.dimension_id) !== String(dimensionId)) errors.push(`Idea ${sourceIdea.id}: category ${categoryId} is in the wrong dimension.`);
        }
    }

    if (recommended.parent_idea_id != null && !sourceIdeas.has(recommended.parent_idea_id)) errors.push(`Idea ${sourceIdea.id}: unknown recommended parent.`);
    if (recommended.parent_idea_id === sourceIdea.id) errors.push(`Idea ${sourceIdea.id}: self parent.`);
}

for (const id of goldIdeas.keys()) if (!sourceIdeas.has(id)) errors.push(`Gold Standard has unknown idea ${id}.`);
if (gold.semantic_relations.length !== source.semantic_relations.length) errors.push('Every current semantic relation must have an audit decision.');

const sourceRelationIds = sortedNumbers(source.semantic_relations.map((relation) => relation.id));
const auditedRelationIds = sortedNumbers(gold.semantic_relations.map((relation) => relation.current_relation_id));
if (!same(sourceRelationIds, auditedRelationIds)) errors.push('Semantic relation audit does not cover the exact source relation IDs.');

if (new Set(evals.cases.map((item) => item.id)).size !== evals.cases.length) errors.push('Duplicate eval case IDs.');
for (const testCase of evals.cases) {
    if (!testCase.input || !testCase.expected) errors.push(`Eval ${testCase.id}: input and expected must be separate objects.`);
    if (!['A', 'B'].includes(testCase.phase)) errors.push(`Eval ${testCase.id}: invalid phase.`);
    if (Object.prototype.hasOwnProperty.call(testCase.input ?? {}, 'expected')) errors.push(`Eval ${testCase.id}: expected answer leaked into input.`);

    const allowedCategories = new Set(testCase.input?.allowed_category_ids ?? []);
    const expectedPrimary = testCase.expected?.primary_category_id;
    if (expectedPrimary != null && allowedCategories.size && !allowedCategories.has(expectedPrimary)) errors.push(`Eval ${testCase.id}: expected primary category is not allowed.`);
    for (const categoryIds of Object.values(testCase.expected?.classification_category_ids ?? {})) {
        for (const categoryId of categoryIds) {
            if (allowedCategories.size && !allowedCategories.has(categoryId)) errors.push(`Eval ${testCase.id}: expected classification category ${categoryId} is not allowed.`);
        }
    }

    const allowedTags = new Set(testCase.input?.allowed_tag_ids ?? []);
    for (const tagId of testCase.expected?.tag_ids ?? []) {
        if (allowedTags.size && !allowedTags.has(tagId)) errors.push(`Eval ${testCase.id}: expected tag ${tagId} is not allowed.`);
    }

    const authorizedParents = new Set((testCase.input?.authorized_parent_candidates ?? []).map((item) => item.idea_id));
    const expectedParent = testCase.expected?.parent_top_1;
    if (expectedParent != null && !authorizedParents.has(expectedParent)) errors.push(`Eval ${testCase.id}: expected parent is unauthorized.`);

    const authorizedRelations = new Set((testCase.input?.authorized_related_candidates ?? []).map((item) => item.idea_id));
    const selectedParent = testCase.input?.reviewed_draft?.selected_parent_idea_id;
    if (selectedParent != null && !authorizedRelations.has(selectedParent)) errors.push(`Eval ${testCase.id}: selected parent is absent from authorized candidates.`);
    for (const relation of testCase.expected?.relations ?? []) {
        if (!authorizedRelations.has(relation.related_idea_id)) errors.push(`Eval ${testCase.id}: expected related idea is unauthorized.`);
        if (!(testCase.input.allowed_relation_types ?? []).includes(relation.type)) errors.push(`Eval ${testCase.id}: expected relation type is not allowed.`);
    }
}

if (evals.cases.length < 12) warnings.push('Evaluation set is small; add cases from other authors before production.');
warnings.push('Text quality, semantic hierarchy choices, and relation rationales still require human review.');

console.log(JSON.stringify({
    valid: errors.length === 0,
    source_ideas: source.ideas.length,
    gold_ideas: gold.ideas.length,
    audited_relations: gold.semantic_relations.length,
    eval_cases: evals.cases.length,
    errors,
    warnings,
}, null, 2));

if (errors.length) process.exit(1);
