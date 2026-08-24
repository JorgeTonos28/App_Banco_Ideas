<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\IdeaStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IdeaPublicationService
{
    public function requestPublication(Idea $idea, User $user): Idea
    {
        if (! in_array($idea->publication_status, Idea::PUBLICATION_REQUESTABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'publication_status' => 'La idea no puede enviarse a revisión desde su estado editorial actual.',
            ]);
        }

        if ($idea->visibility === 'draft') {
            throw ValidationException::withMessages([
                'visibility' => 'Convierte el borrador en una idea privada antes de solicitar publicación.',
            ]);
        }

        return DB::transaction(function () use ($idea, $user): Idea {
            $oldStatus = $idea->publication_status;

            $idea->update([
                'publication_status' => 'pending_review',
                'community_display' => 'hidden',
                'publication_requested_at' => now(),
                'publication_requested_by_user_id' => $user->id,
                'publication_reviewed_at' => null,
                'publication_reviewed_by_user_id' => null,
                'publication_notes' => null,
            ]);

            $this->recordTransition($idea, $user, 'publication', $oldStatus, 'pending_review', 'Solicitud de publicación enviada al equipo de innovación.');

            return $idea->refresh();
        });
    }

    public function cancelRequest(Idea $idea, User $user): Idea
    {
        if ($idea->publication_status !== 'pending_review') {
            throw ValidationException::withMessages([
                'publication_status' => 'Solo puede cancelarse una solicitud pendiente.',
            ]);
        }

        return DB::transaction(function () use ($idea, $user): Idea {
            $idea->update([
                'publication_status' => 'not_submitted',
                'community_display' => 'hidden',
                'publication_requested_at' => null,
                'publication_requested_by_user_id' => null,
            ]);

            $this->recordTransition($idea, $user, 'publication', 'pending_review', 'not_submitted', 'Solicitud de publicación cancelada por el autor.');

            return $idea->refresh();
        });
    }

    public function review(Idea $idea, User $reviewer, string $status, string $display, ?string $notes): Idea
    {
        if (! in_array($status, Idea::PUBLICATION_REVIEW_STATUSES, true)) {
            throw ValidationException::withMessages([
                'publication_status' => 'La decisión editorial seleccionada no es válida.',
            ]);
        }

        return DB::transaction(function () use ($idea, $reviewer, $status, $display, $notes): Idea {
            $oldStatus = $idea->publication_status;
            $isPublished = $status === 'published';

            $idea->update([
                'publication_status' => $status,
                'community_display' => $isPublished ? $display : 'hidden',
                'visibility' => $isPublished ? 'public' : 'private',
                'publication_reviewed_at' => now(),
                'publication_reviewed_by_user_id' => $reviewer->id,
                'published_at' => $isPublished ? ($idea->published_at ?? now()) : $idea->published_at,
                'publication_notes' => $notes,
            ]);

            $comment = $notes ?: match ($status) {
                'published' => 'Idea aprobada para publicación en la comunidad.',
                'changes_requested' => 'El equipo de innovación solicitó ajustes antes de publicar.',
                'rejected' => 'Solicitud de publicación rechazada.',
                'unpublished' => 'Idea retirada de la comunidad.',
            };

            $this->recordTransition($idea, $reviewer, 'publication', $oldStatus, $status, $comment);

            return $idea->refresh();
        });
    }

    private function recordTransition(Idea $idea, User $user, string $workflow, ?string $oldStatus, string $newStatus, string $comment): void
    {
        IdeaStatusHistory::create([
            'idea_id' => $idea->id,
            'user_id' => $user->id,
            'workflow' => $workflow,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'comment' => $comment,
        ]);
    }
}
