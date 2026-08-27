<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\IdeaRelation;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IdeaRelationService
{
    public function create(Idea $source, Idea $target, User $actor, string $type, ?string $rationale = null): IdeaRelation
    {
        if ($source->is($target)) {
            throw ValidationException::withMessages([
                'target_idea_id' => 'Selecciona una idea diferente para crear la relación.',
            ]);
        }

        if (! $actor->isAdmin()
            && $source->user_id !== $target->user_id
            && ! $target->user->can('view', $source)) {
            throw ValidationException::withMessages([
                'target_idea_id' => 'La relación no puede proponerse porque el otro autor no tiene acceso a la idea de origen.',
            ]);
        }

        $autoApprove = $actor->isAdmin() || $source->user_id === $target->user_id;

        try {
            return IdeaRelation::create([
                'source_idea_id' => $source->id,
                'target_idea_id' => $target->id,
                'type' => $type,
                'status' => $autoApprove ? 'approved' : 'pending',
                'rationale' => $rationale,
                'created_by_user_id' => $actor->id,
                'reviewed_by_user_id' => $autoApprove ? $actor->id : null,
                'reviewed_at' => $autoApprove ? now() : null,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'target_idea_id' => 'Esta relación ya existe entre las ideas seleccionadas.',
            ]);
        }
    }

    public function review(IdeaRelation $relation, User $reviewer, string $status): IdeaRelation
    {
        if (! in_array($status, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'status' => 'La decisión sobre la relación no es válida.',
            ]);
        }

        return DB::transaction(function () use ($relation, $reviewer, $status): IdeaRelation {
            $relation->update([
                'status' => $status,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            return $relation->refresh();
        });
    }

    public function updateDetails(
        IdeaRelation $relation,
        User $actor,
        string $type,
        ?string $rationale = null
    ): IdeaRelation {
        return DB::transaction(function () use ($relation, $actor, $type, $rationale): IdeaRelation {
            $detailsChanged = $relation->type !== $type || $relation->rationale !== $rationale;

            if (! $detailsChanged) {
                return $relation;
            }

            $crossAuthorRelation = $relation->sourceIdea->user_id !== $relation->targetIdea->user_id;
            $requiresConfirmation = $crossAuthorRelation && ! $actor->isAdmin();

            $relation->update([
                'type' => $type,
                'rationale' => $rationale,
                'status' => $requiresConfirmation ? 'pending' : 'approved',
                'reviewed_by_user_id' => $requiresConfirmation ? null : $actor->id,
                'reviewed_at' => $requiresConfirmation ? null : now(),
            ]);

            return $relation->refresh();
        });
    }
}
