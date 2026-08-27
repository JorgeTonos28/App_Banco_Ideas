<?php

namespace App\AI\Services;

use App\Models\IdeaRelation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AiResponseValidator
{
    public function organization(array $output, array $context): array
    {
        $validator = Validator::make($output, [
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:10000'],
            'problem_opportunity' => ['nullable', 'string', 'max:5000'],
            'primary_category_id' => ['nullable', 'integer', Rule::in($context['allowed_category_ids'])],
            'classifications' => ['required', 'array', 'max:10'],
            'classifications.*.dimension_id' => ['required', 'integer', 'distinct', Rule::in($context['allowed_dimension_ids'])],
            'classifications.*.category_ids' => ['required', 'array', 'max:20'],
            'classifications.*.category_ids.*' => ['integer', 'distinct', Rule::in($context['allowed_category_ids'])],
            'tags' => ['required', 'array', 'max:'.config('ai.limits.suggested_tags', 7)],
            'tags.*.name' => ['required', 'string', 'min:2', 'max:50'],
            'tags.*.existing_tag_id' => ['nullable', 'integer', Rule::in($context['allowed_tag_ids'])],
            'tags.*.action' => ['required', Rule::in(['reuse_existing', 'propose_new'])],
            'tags.*.confidence' => ['required', 'numeric', 'between:0,1'],
            'parent_suggestion.idea_id' => ['nullable', 'integer', Rule::in($context['allowed_parent_idea_ids'])],
            'parent_suggestion.confidence' => ['required', 'numeric', 'between:0,1'],
            'parent_suggestion.rationale' => ['required', 'string', 'max:1000'],
            'missing_information' => ['present', 'array', 'max:10'],
            'missing_information.*' => ['string', 'max:500'],
            'confidence' => ['required', 'numeric', 'between:0,1'],
        ]);

        $validator->after(function ($validator) use ($output, $context): void {
            $dimensions = collect($context['taxonomy'])->keyBy('id');
            $categoryDimensionMap = collect($context['taxonomy'])
                ->flatMap(fn (array $dimension) => collect($dimension['categories'])->map(fn (array $category) => [
                    'category_id' => $category['id'],
                    'dimension_id' => $dimension['id'],
                ]))
                ->mapWithKeys(fn (array $item) => [$item['category_id'] => $item['dimension_id']]);

            foreach ($output['classifications'] ?? [] as $index => $selection) {
                $dimension = $dimensions->get($selection['dimension_id'] ?? 0);
                $categoryIds = collect($selection['category_ids'] ?? []);

                if (! $dimension || $categoryIds->contains(fn ($id) => $categoryDimensionMap->get($id) !== $dimension['id'])) {
                    $validator->errors()->add("classifications.{$index}", 'La clasificación no coincide con su dimensión.');

                    continue;
                }

                if ($dimension['selection_mode'] === 'single' && $categoryIds->count() > 1) {
                    $validator->errors()->add("classifications.{$index}", 'La dimensión sólo permite una categoría.');
                }
            }

            $primaryDimension = $dimensions->firstWhere('is_primary', true);
            if (($output['primary_category_id'] ?? null) !== null
                && $categoryDimensionMap->get($output['primary_category_id']) !== ($primaryDimension['id'] ?? null)) {
                $validator->errors()->add('primary_category_id', 'La categoría principal no pertenece a la dimensión principal.');
            }

            $selectionsByDimension = collect($output['classifications'] ?? [])->keyBy('dimension_id');
            foreach ($dimensions->where('is_required', true) as $dimension) {
                if (! $selectionsByDimension->has($dimension['id'])
                    || collect($selectionsByDimension->get($dimension['id'])['category_ids'] ?? [])->isEmpty()) {
                    $validator->errors()->add('classifications', "Falta una selección para {$dimension['name']}.");
                }
            }

            if (($output['primary_category_id'] ?? null) !== null
                && ! collect($selectionsByDimension->get($primaryDimension['id'] ?? 0)['category_ids'] ?? [])->contains($output['primary_category_id'])) {
                $validator->errors()->add('primary_category_id', 'La categoría principal no coincide con la clasificación primaria.');
            }

            foreach ($output['tags'] ?? [] as $index => $tag) {
                $hasExistingId = isset($tag['existing_tag_id']);
                if (($tag['action'] ?? null) === 'reuse_existing' && ! $hasExistingId) {
                    $validator->errors()->add("tags.{$index}.existing_tag_id", 'La etiqueta reutilizada necesita un ID existente.');
                }
                if (($tag['action'] ?? null) === 'propose_new' && $hasExistingId) {
                    $validator->errors()->add("tags.{$index}.existing_tag_id", 'Una etiqueta nueva no puede declarar un ID existente.');
                }
            }
        });

        $validated = $validator->validate();
        $tagNames = [];
        $tagsById = collect($context['tag_candidates'])->keyBy('id');
        $categoriesById = collect($context['taxonomy'])
            ->flatMap(fn (array $dimension) => $dimension['categories'])
            ->keyBy('id');
        $ideasById = collect($context['idea_candidates'])->keyBy('id');

        foreach ($validated['tags'] as $tag) {
            $name = $tag['existing_tag_id']
                ? $tagsById->get($tag['existing_tag_id'])['name']
                : Str::squish(strip_tags($tag['name']));
            $key = mb_strtolower($name);

            if (isset($tagNames[$key])) {
                continue;
            }

            $tagNames[$key] = [
                'name' => $name,
                'existing_tag_id' => $tag['existing_tag_id'],
                'action' => $tag['action'],
                'confidence' => round((float) $tag['confidence'], 2),
            ];
        }

        return [
            'title' => Str::squish(strip_tags($validated['title'])),
            'description' => trim(strip_tags($validated['description'])),
            'problem_opportunity' => filled($validated['problem_opportunity'] ?? null) ? trim(strip_tags($validated['problem_opportunity'])) : null,
            'primary_category_id' => $validated['primary_category_id'],
            'primary_category_name' => $validated['primary_category_id'] ? $categoriesById->get($validated['primary_category_id'])['path'] : null,
            'classifications' => collect($validated['classifications'])->map(fn (array $selection) => [
                'dimension_id' => (int) $selection['dimension_id'],
                'category_ids' => collect($selection['category_ids'])->map(fn ($id) => (int) $id)->unique()->values()->all(),
            ])->values()->all(),
            'tags' => array_values($tagNames),
            'parent_suggestion' => [
                'idea_id' => $validated['parent_suggestion']['idea_id'],
                'idea_title' => $validated['parent_suggestion']['idea_id'] ? $ideasById->get($validated['parent_suggestion']['idea_id'])['title'] : null,
                'confidence' => round((float) $validated['parent_suggestion']['confidence'], 2),
                'rationale' => Str::limit(trim(strip_tags($validated['parent_suggestion']['rationale'])), 1000, ''),
            ],
            'missing_information' => collect($validated['missing_information'])->map(fn ($item) => Str::limit(Str::squish(strip_tags($item)), 500, ''))->filter()->values()->all(),
            'confidence' => round((float) $validated['confidence'], 2),
        ];
    }

    public function relations(array $output, array $context): array
    {
        $validated = Validator::make($output, [
            'relations' => ['present', 'array', 'max:'.config('ai.limits.suggested_relations', 5)],
            'relations.*.target_idea_id' => ['required', 'integer', 'distinct', Rule::in($context['allowed_idea_ids'])],
            'relations.*.type' => ['required', Rule::in(IdeaRelation::TYPES)],
            'relations.*.rationale' => ['required', 'string', 'max:1000'],
            'relations.*.confidence' => ['required', 'numeric', 'between:0,1'],
            'confidence' => ['required', 'numeric', 'between:0,1'],
        ])->validate();

        $ideasById = collect($context['idea_candidates'])->keyBy('id');

        return [
            'relations' => collect($validated['relations'])->map(fn (array $relation) => [
                'target_idea_id' => (int) $relation['target_idea_id'],
                'target_title' => $ideasById->get($relation['target_idea_id'])['title'],
                'type' => $relation['type'],
                'rationale' => Str::limit(trim(strip_tags($relation['rationale'])), 1000, ''),
                'confidence' => round((float) $relation['confidence'], 2),
            ])->values()->all(),
            'confidence' => round((float) $validated['confidence'], 2),
        ];
    }
}
