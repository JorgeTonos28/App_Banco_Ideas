<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\IdeaHierarchyHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IdeaHierarchyService
{
    public function move(Idea $idea, ?Idea $parent, User $actor, ?string $note = null): Idea
    {
        $this->validateMove($idea, $parent, $actor);

        if ($idea->parent_idea_id === $parent?->id) {
            return $idea;
        }

        return DB::transaction(function () use ($idea, $parent, $actor, $note): Idea {
            $oldParentId = $idea->parent_idea_id;

            $idea->update(['parent_idea_id' => $parent?->id]);

            IdeaHierarchyHistory::create([
                'idea_id' => $idea->id,
                'old_parent_idea_id' => $oldParentId,
                'new_parent_idea_id' => $parent?->id,
                'changed_by_user_id' => $actor->id,
                'note' => $note,
            ]);

            return $idea->refresh();
        });
    }

    private function validateMove(Idea $idea, ?Idea $parent, User $actor): void
    {
        if ($parent?->is($idea)) {
            throw ValidationException::withMessages([
                'parent_idea_id' => 'Una idea no puede ser su propia idea madre.',
            ]);
        }

        if ($idea->isPublished() && $idea->community_display === 'represented_by_parent' && ! $parent) {
            throw ValidationException::withMessages([
                'parent_idea_id' => 'Una idea representada en la comunidad debe conservar una idea madre.',
            ]);
        }

        if ($parent && ! $actor->isAdmin() && $parent->user_id !== $actor->id) {
            throw ValidationException::withMessages([
                'parent_idea_id' => 'Solo puedes organizar tus ideas bajo otra idea propia.',
            ]);
        }

        if ($parent && ! $actor->can('view', $parent)) {
            throw ValidationException::withMessages([
                'parent_idea_id' => 'No tienes acceso a la idea madre seleccionada.',
            ]);
        }

        $current = $parent;
        $visited = [];

        while ($current) {
            if ($current->is($idea)) {
                throw ValidationException::withMessages([
                    'parent_idea_id' => 'El cambio crearía un ciclo en la jerarquía de ideas.',
                ]);
            }

            if (isset($visited[$current->id])) {
                throw ValidationException::withMessages([
                    'parent_idea_id' => 'La jerarquía seleccionada contiene un ciclo inválido.',
                ]);
            }

            $visited[$current->id] = true;
            $current = $current->parentIdea;
        }
    }
}
