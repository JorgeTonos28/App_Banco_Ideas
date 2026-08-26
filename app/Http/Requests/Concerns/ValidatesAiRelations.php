<?php

namespace App\Http\Requests\Concerns;

use App\Models\Idea;
use App\Models\IdeaRelation;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesAiRelations
{
    protected function aiRelationRules(): array
    {
        return [
            'ai_relations' => ['nullable', 'array', 'max:5'],
            'ai_relations.*.target_idea_id' => ['required', 'integer', 'distinct'],
            'ai_relations.*.type' => ['required', Rule::in(IdeaRelation::TYPES)],
            'ai_relations.*.rationale' => ['required', 'string', 'max:1000'],
        ];
    }

    protected function validateAiRelations(Validator $validator, ?Idea $currentIdea = null): void
    {
        $targetIds = collect($this->input('ai_relations', []))->pluck('target_idea_id')->filter()->map(fn ($id) => (int) $id);

        if ($currentIdea && $targetIds->contains($currentIdea->id)) {
            $validator->errors()->add('ai_relations', 'Una idea no puede relacionarse consigo misma.');
        }

        $allowedCount = Idea::query()
            ->whereIn('id', $targetIds)
            ->where('user_id', $this->user()->id)
            ->whereNotIn('workspace_status', ['archivada', 'descartada'])
            ->count();

        if ($allowedCount !== $targetIds->unique()->count()) {
            $validator->errors()->add('ai_relations', 'Una relación sugerida apunta a una idea no disponible.');
        }
    }
}
