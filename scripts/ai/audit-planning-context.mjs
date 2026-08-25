import fs from 'node:fs';
import path from 'node:path';

const sourcePath = process.argv[2];

if (!sourcePath) {
    console.error('Usage: node scripts/ai/audit-planning-context.mjs <ai-planning-context.json>');
    process.exit(1);
}

const data = JSON.parse(fs.readFileSync(path.resolve(sourcePath), 'utf8'));
const ideas = Array.isArray(data.ideas) ? data.ideas : [];
const dimensions = Array.isArray(data.taxonomy?.dimensions) ? data.taxonomy.dimensions : [];
const categories = Array.isArray(data.taxonomy?.categories) ? data.taxonomy.categories : [];
const tags = Array.isArray(data.taxonomy?.tags) ? data.taxonomy.tags : [];
const relations = Array.isArray(data.semantic_relations) ? data.semantic_relations : [];

const ideaById = new Map(ideas.map((idea) => [idea.id, idea]));
const dimensionById = new Map(dimensions.map((dimension) => [dimension.id, dimension]));
const categoryById = new Map(categories.map((category) => [category.id, category]));
const tagById = new Map(tags.map((tag) => [tag.id, tag]));

const duplicates = (values) => {
    const counts = new Map();
    for (const value of values) counts.set(value, (counts.get(value) ?? 0) + 1);
    return [...counts.entries()].filter(([, count]) => count > 1).map(([value, count]) => ({ value, count }));
};

const normalize = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('es')
    .replace(/[^a-z0-9]+/g, ' ')
    .trim()
    .replace(/\s+/g, ' ');

const stem = (value) => normalize(value)
    .split(' ')
    .map((token) => {
        if (token.length > 4 && token.endsWith('ces')) return `${token.slice(0, -3)}z`;
        if (token.length > 4 && token.endsWith('es') && /[rlndzjm]$/.test(token.slice(0, -2))) return token.slice(0, -2);
        if (token.length > 3 && token.endsWith('s') && /[aeiou]$/.test(token.slice(0, -1))) return token.slice(0, -1);
        return token;
    })
    .join(' ');

const issues = [];
const addIssue = (severity, code, evidence) => issues.push({ severity, code, evidence });

for (const duplicate of duplicates(ideas.map((idea) => idea.id))) addIssue('critical', 'duplicate_idea_id', duplicate);
for (const duplicate of duplicates(dimensions.map((dimension) => dimension.id))) addIssue('critical', 'duplicate_dimension_id', duplicate);
for (const duplicate of duplicates(categories.map((category) => category.id))) addIssue('critical', 'duplicate_category_id', duplicate);
for (const duplicate of duplicates(tags.map((tag) => tag.id))) addIssue('critical', 'duplicate_tag_id', duplicate);
for (const duplicate of duplicates(relations.map((relation) => relation.id))) addIssue('critical', 'duplicate_relation_id', duplicate);

const expectedStats = {
    ideas: ideas.length,
    dimensions: dimensions.length,
    categories: categories.length,
    system_tags: tags.length,
    semantic_relations_inside_export: relations.length,
};

for (const [key, actual] of Object.entries(expectedStats)) {
    if (data.statistics?.[key] !== actual) {
        addIssue('high', 'statistics_mismatch', { key, declared: data.statistics?.[key], actual });
    }
}

const hierarchyCycles = [];
for (const idea of ideas) {
    const seen = new Set([idea.id]);
    let current = idea;
    while (current?.hierarchy?.parent_idea_id != null) {
        const parentId = current.hierarchy.parent_idea_id;
        if (!ideaById.has(parentId)) {
            addIssue('high', 'missing_parent_reference', { idea_id: idea.id, parent_idea_id: parentId });
            break;
        }
        if (seen.has(parentId)) {
            hierarchyCycles.push({ idea_id: idea.id, repeated_idea_id: parentId });
            break;
        }
        seen.add(parentId);
        current = ideaById.get(parentId);
    }
}
for (const cycle of hierarchyCycles) addIssue('critical', 'hierarchy_cycle', cycle);

for (const idea of ideas) {
    const declaredChildren = new Set(idea.hierarchy?.children_idea_ids_inside_export ?? []);
    const actualChildren = new Set(ideas.filter((candidate) => candidate.hierarchy?.parent_idea_id === idea.id).map((candidate) => candidate.id));
    const missing = [...actualChildren].filter((id) => !declaredChildren.has(id));
    const extra = [...declaredChildren].filter((id) => !actualChildren.has(id));
    if (missing.length || extra.length) addIssue('high', 'children_projection_mismatch', { idea_id: idea.id, missing, extra });

    const selectedByDimension = new Map();
    for (const classification of idea.classifications ?? []) {
        const category = categoryById.get(classification.category_id);
        if (!dimensionById.has(classification.dimension_id)) {
            addIssue('critical', 'unknown_dimension_reference', { idea_id: idea.id, dimension_id: classification.dimension_id });
        }
        if (!category) {
            addIssue('critical', 'unknown_category_reference', { idea_id: idea.id, category_id: classification.category_id });
            continue;
        }
        if (category.dimension_id !== classification.dimension_id) {
            addIssue('critical', 'category_dimension_mismatch', { idea_id: idea.id, category_id: category.id, expected_dimension_id: category.dimension_id, actual_dimension_id: classification.dimension_id });
        }
        if (!category.is_active) addIssue('high', 'inactive_category_selected', { idea_id: idea.id, category_id: category.id });
        const selections = selectedByDimension.get(classification.dimension_id) ?? [];
        selections.push(classification.category_id);
        selectedByDimension.set(classification.dimension_id, selections);
    }

    for (const dimension of dimensions.filter((item) => item.is_required && item.is_active)) {
        const selections = selectedByDimension.get(dimension.id) ?? [];
        if (!selections.length) addIssue('high', 'required_dimension_missing', { idea_id: idea.id, dimension_id: dimension.id });
        if (dimension.selection_mode === 'single' && selections.length > 1) {
            addIssue('high', 'single_dimension_has_multiple_values', { idea_id: idea.id, dimension_id: dimension.id, category_ids: selections });
        }
    }

    if (!categoryById.has(idea.primary_category?.id)) {
        addIssue('critical', 'unknown_primary_category', { idea_id: idea.id, category_id: idea.primary_category?.id });
    } else if (!(selectedByDimension.get(idea.primary_category.dimension_id) ?? []).includes(idea.primary_category.id)) {
        addIssue('high', 'primary_category_not_classified', { idea_id: idea.id, category_id: idea.primary_category.id });
    }

    for (const tag of idea.tags ?? []) {
        if (!tagById.has(tag.id)) addIssue('critical', 'unknown_tag_reference', { idea_id: idea.id, tag_id: tag.id });
    }
}

const relationPairKeys = [];
for (const relation of relations) {
    if (!ideaById.has(relation.source_idea_id)) addIssue('critical', 'unknown_relation_source', { relation_id: relation.id, idea_id: relation.source_idea_id });
    if (!ideaById.has(relation.target_idea_id)) addIssue('critical', 'unknown_relation_target', { relation_id: relation.id, idea_id: relation.target_idea_id });
    if (relation.source_idea_id === relation.target_idea_id) addIssue('critical', 'self_relation', { relation_id: relation.id });
    relationPairKeys.push(`${Math.min(relation.source_idea_id, relation.target_idea_id)}:${Math.max(relation.source_idea_id, relation.target_idea_id)}`);
}
for (const duplicate of duplicates(relationPairKeys)) addIssue('medium', 'duplicate_relation_pair', duplicate);

const tagCanonicalGroups = new Map();
for (const tag of tags) {
    const key = stem(tag.name);
    const group = tagCanonicalGroups.get(key) ?? [];
    group.push({ id: tag.id, name: tag.name, ideas_count_systemwide: tag.ideas_count_systemwide });
    tagCanonicalGroups.set(key, group);
}

const candidateTagDuplicates = [...tagCanonicalGroups.entries()]
    .filter(([, group]) => group.length > 1)
    .map(([canonical, group]) => ({ canonical, tags: group }));

const tagCounts = ideas.map((idea) => ({ idea_id: idea.id, count: idea.tags?.length ?? 0 }));
const missingProblemIds = ideas.filter((idea) => !String(idea.content?.problem_opportunity ?? '').trim()).map((idea) => idea.id);
const generatedSummaryIds = ideas
    .filter((idea) => {
        const summary = normalize(String(idea.content?.summary ?? '').replace(/\.{3,}$/u, ''));
        const description = normalize(idea.content?.description ?? '');
        return summary.length > 0 && description.startsWith(summary.slice(0, Math.min(summary.length, 120)));
    })
    .map((idea) => idea.id);

const output = {
    source: {
        schema_version: data.schema_version,
        exported_at: data.exported_at,
        declared_statistics: data.statistics,
    },
    profile: {
        ideas: ideas.length,
        roots: ideas.filter((idea) => idea.hierarchy?.parent_idea_id == null).length,
        subideas: ideas.filter((idea) => idea.hierarchy?.parent_idea_id != null).length,
        dimensions: dimensions.length,
        categories: categories.length,
        active_categories: categories.filter((category) => category.is_active).length,
        inactive_categories: categories.filter((category) => !category.is_active).length,
        tags: tags.length,
        unused_tags: tags.filter((tag) => tag.ideas_count_systemwide === 0).length,
        relations: relations.length,
        ideas_missing_problem_opportunity: missingProblemIds,
        ideas_with_generated_summary_pattern: generatedSummaryIds,
        ideas_below_four_tags: tagCounts.filter((item) => item.count < 4),
        ideas_above_seven_tags: tagCounts.filter((item) => item.count > 7),
        candidate_tag_duplicate_groups: candidateTagDuplicates,
    },
    structural_issues: issues,
};

console.log(JSON.stringify(output, null, 2));
