<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Task $task): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $task->created_by_user_id === $user->id || $task->assigned_to_user_id === $user->id) {
            return true;
        }

        if ($task->volunteers()->where('user_id', $user->id)->where('status', 'approved')->exists()) {
            return true;
        }

        return $task->participation_mode === 'open'
            && $task->idea
            && $task->idea->allow_task_collaboration
            && $user->can('view', $task->idea);
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->is_active
            && ($user->isAdmin() || $task->created_by_user_id === $user->id);
    }

    public function changeStatus(User $user, Task $task): bool
    {
        return $this->update($user, $task)
            || ($user->is_active && $task->assigned_to_user_id === $user->id)
            || ($user->is_active && $task->volunteers()
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->exists());
    }

    public function uploadAttachment(User $user, Task $task): bool
    {
        return $this->changeStatus($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    public function volunteer(User $user, Task $task): bool
    {
        return $user->is_active
            && $task->created_by_user_id !== $user->id
            && $task->assigned_to_user_id !== $user->id
            && $task->participation_mode === 'open'
            && $task->idea
            && $task->idea->allow_task_collaboration
            && $user->can('view', $task->idea)
            && ! in_array($task->status, ['completada', 'cancelada'], true);
    }

    public function reviewVolunteers(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }
}
