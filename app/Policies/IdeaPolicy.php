<?php

namespace App\Policies;

use App\Models\Idea;
use App\Models\User;

class IdeaPolicy
{
    /**
     * Determine whether the user can view any ideas.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the idea.
     */
    public function view(?User $user, Idea $idea): bool
    {
        if ($idea->isPublished()) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->isAdmin()
            || $idea->user_id === $user->id
            || $idea->isAccessibleToAuthenticatedAudience();
    }

    /**
     * Determine whether the user can create ideas.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can update the idea.
     */
    public function update(User $user, Idea $idea): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $idea->isEditableBy($user);
    }

    /**
     * Determine whether the user can delete the idea.
     */
    public function delete(User $user, Idea $idea): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($idea->children()
            ->published()
            ->where('community_display', 'represented_by_parent')
            ->exists()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $idea->user_id === $user->id
            && $idea->publication_status === 'not_submitted'
            && ($idea->visibility === 'draft' || $idea->workspace_status === 'capturada');
    }

    /**
     * Determine whether the user can rate/vote on the idea.
     */
    public function vote(User $user, Idea $idea): bool
    {
        if (! $user->is_active) {
            return false;
        }

        // Cannot vote on own idea
        if ($idea->user_id === $user->id) {
            return false;
        }

        return $idea->isPublished() && ! in_array($idea->status, ['descartada', 'archivada'], true);
    }

    /**
     * Determine whether the user can change status / manage administrative attributes.
     */
    public function manage(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function requestPublication(User $user, Idea $idea): bool
    {
        return $user->is_active
            && ($user->isAdmin() || $idea->user_id === $user->id)
            && $idea->canRequestPublication();
    }

    public function cancelPublication(User $user, Idea $idea): bool
    {
        return $user->is_active
            && ($user->isAdmin() || $idea->user_id === $user->id)
            && $idea->publication_status === 'pending_review';
    }

    public function reviewPublication(User $user, Idea $idea): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function organize(User $user, Idea $idea): bool
    {
        return $user->is_active
            && ($user->isAdmin() || $idea->user_id === $user->id);
    }
}
