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
        if ($idea->visibility === 'public') {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $user->isAdmin() || $idea->user_id === $user->id;
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
        if (!$user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Author can only update their own idea if it's in 'nueva' state or a draft
        return $idea->user_id === $user->id && (in_array($idea->status, ['nueva']) || $idea->visibility === 'draft');
    }

    /**
     * Determine whether the user can delete the idea.
     */
    public function delete(User $user, Idea $idea): bool
    {
        if (!$user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // User can only delete if it's a draft or in 'nueva' status
        return $idea->user_id === $user->id && (in_array($idea->status, ['nueva']) || $idea->visibility === 'draft');
    }

    /**
     * Determine whether the user can rate/vote on the idea.
     */
    public function vote(User $user, Idea $idea): bool
    {
        if (!$user->is_active) {
            return false;
        }

        // Cannot vote on own idea
        if ($idea->user_id === $user->id) {
            return false;
        }

        // Idea must be public and not discarded/archived
        return $idea->visibility === 'public' && !in_array($idea->status, ['descartada', 'archivada']);
    }

    /**
     * Determine whether the user can change status / manage administrative attributes.
     */
    public function manage(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
