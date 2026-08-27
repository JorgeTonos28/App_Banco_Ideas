<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Collection;

class TaskTreeService
{
    public function prepare(Collection $tasks): array
    {
        $tasks = $tasks->values();
        $ids = $tasks->pluck('id')->all();

        return [
            'roots' => $tasks->filter(fn (Task $task) => ! $task->parent_task_id
                || ! in_array($task->parent_task_id, $ids, true))->values(),
            'byParent' => $tasks->whereNotNull('parent_task_id')->groupBy('parent_task_id'),
        ];
    }

    public function descendantIds(Task $task): Collection
    {
        $ids = collect();
        $pending = collect([$task->id]);

        while ($pending->isNotEmpty()) {
            $children = Task::query()->whereIn('parent_task_id', $pending)->pluck('id');
            $newIds = $children->diff($ids);
            $ids = $ids->concat($newIds)->unique()->values();
            $pending = $newIds;
        }

        return $ids;
    }
}
