<?php

namespace App\Policies;

use App\Models\IdeaComment;
use App\Models\User;

class CommentPolicy
{
    public function update(User $user, IdeaComment $comment): bool
    {
        return $user->is_active && ($user->isAdmin() || $comment->user_id === $user->id);
    }

    public function delete(User $user, IdeaComment $comment): bool
    {
        return $user->is_active && ($user->isAdmin() || $comment->user_id === $user->id || $comment->idea->user_id === $user->id);
    }
}
