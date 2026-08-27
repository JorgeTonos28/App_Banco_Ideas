<?php

namespace App\Http\Requests\Concerns;

use App\Models\Idea;
use App\Models\IdeaRelation;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesIdeaRelations
{
    protected function ideaRelationRules(): array
    {
        return [
            'idea_relations_present' => ['sometimes', 'boolean'],
            'idea_relations' => ['nullable', 'array', 'max:20'],
            'idea_relations.*.id' => ['nullable', 'integer', 'distinct', 'exists:idea_relations,id'],
            'idea_relations.*.target_idea_id' => ['required', 'integer', 'exists:ideas,id'],
            'idea_relations.*.type' => ['required', Rule::in(IdeaRelation::TYPES)],
            'idea_relations.*.rationale' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function validateIdeaRelations(Validator $validator, ?Idea $currentIdea = null): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $relations = collect($this->input('idea_relations', []));
        $keys = $relations->map(fn (array $relation): string => sprintf(
            '%d:%s',
            (int) ($relation['target_idea_id'] ?? 0),
            (string) ($relation['type'] ?? '')
        ));

        if ($keys->count() !== $keys->unique()->count()) {
            $validator->errors()->add('idea_relations', 'No puedes repetir el mismo tipo de relación con una idea.');
        }

        $existingIds = $relations->pluck('id')->filter()->map(fn ($id) => (int) $id);
        if (! $currentIdea && $existingIds->isNotEmpty()) {
            $validator->errors()->add('idea_relations', 'Las relaciones existentes no son válidas al crear una idea.');

            return;
        }

        $existingRelations = $currentIdea
            ? $currentIdea->outgoingRelations()->whereIn('id', $existingIds)->get()->keyBy('id')
            : collect();

        if ($existingRelations->count() !== $existingIds->unique()->count()) {
            $validator->errors()->add('idea_relations', 'Una de las relaciones existentes no pertenece a esta idea.');
        }

        $user = $this->user();

        foreach ($relations as $index => $relation) {
            $targetId = (int) $relation['target_idea_id'];
            $existingId = isset($relation['id']) ? (int) $relation['id'] : null;

            if ($currentIdea && $targetId === $currentIdea->id) {
                $validator->errors()->add("idea_relations.{$index}.target_idea_id", 'Una idea no puede relacionarse consigo misma.');

                continue;
            }

            if ($existingId) {
                $existing = $existingRelations->get($existingId);
                if (! $existing || ! $user->can('update', $existing)) {
                    $validator->errors()->add("idea_relations.{$index}.id", 'No puedes modificar esta relación.');
                } elseif ($existing->target_idea_id !== $targetId) {
                    $validator->errors()->add("idea_relations.{$index}.target_idea_id", 'La idea conectada de una relación existente no puede sustituirse.');
                }

                continue;
            }

            $target = Idea::find($targetId);
            if (! $target || ! $user->can('view', $target) || in_array($target->workspace_status, ['archivada', 'descartada'], true)) {
                $validator->errors()->add("idea_relations.{$index}.target_idea_id", 'La idea conectada no está disponible.');

                continue;
            }

            $sourceAllowsCrossAuthor = $user->isAdmin() || ($currentIdea?->isPublished() ?? false);
            if ($target->user_id !== $user->id && ! $sourceAllowsCrossAuthor) {
                $validator->errors()->add(
                    "idea_relations.{$index}.target_idea_id",
                    'Las ideas privadas sólo pueden conectarse con otras ideas del mismo autor.'
                );
            }
        }
    }
}
