<?php

namespace App\Policies;

use App\Models\IdeaRelation;
use App\Models\User;

class IdeaRelationPolicy
{
    public function view(User $user, IdeaRelation $relation): bool
    {
        return $user->can('view', $relation->sourceIdea)
            && $user->can('view', $relation->targetIdea);
    }

    public function review(User $user, IdeaRelation $relation): bool
    {
        return $user->is_active
            && ($user->isAdmin() || $relation->targetIdea->user_id === $user->id);
    }

    public function delete(User $user, IdeaRelation $relation): bool
    {
        return $user->is_active
            && ($user->isAdmin() || $relation->sourceIdea->user_id === $user->id);
    }
}
