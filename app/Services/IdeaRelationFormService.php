<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class IdeaRelationFormService
{
    public function __construct(private readonly IdeaRelationService $relationService) {}

    public function sync(Idea $source, User $actor, array $relations): void
    {
        $existingRelations = $source->outgoingRelations()
            ->with(['sourceIdea', 'targetIdea'])
            ->get()
            ->keyBy('id');
        $submittedExistingIds = collect($relations)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        foreach ($existingRelations->except($submittedExistingIds->all()) as $relation) {
            if (! $actor->can('delete', $relation)) {
                throw ValidationException::withMessages([
                    'idea_relations' => 'No puedes eliminar una de las relaciones existentes.',
                ]);
            }

            $relation->delete();
        }

        foreach ($relations as $index => $relationData) {
            $existingId = isset($relationData['id']) ? (int) $relationData['id'] : null;
            $targetId = (int) $relationData['target_idea_id'];
            $type = (string) $relationData['type'];
            $rationale = trim((string) ($relationData['rationale'] ?? ''));
            $rationale = $rationale === '' ? null : $rationale;

            if ($existingId) {
                $relation = $existingRelations->get($existingId);
                if (! $relation || ! $actor->can('update', $relation) || $relation->target_idea_id !== $targetId) {
                    throw ValidationException::withMessages([
                        'idea_relations' => 'Una de las relaciones existentes no puede modificarse.',
                    ]);
                }

                $this->relationService->updateDetails($relation, $actor, $type, $rationale);

                continue;
            }

            $target = Idea::find($targetId);
            if (! $target || ! $actor->can('view', $target)) {
                throw ValidationException::withMessages([
                    'idea_relations' => 'La idea conectada no está disponible.',
                ]);
            }

            try {
                $this->relationService->create($source, $target, $actor, $type, $rationale);
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages([
                    "idea_relations.{$index}.target_idea_id" => collect($exception->errors())->flatten()->first()
                        ?: 'La relación propuesta no está disponible.',
                ]);
            }
        }
    }
}
