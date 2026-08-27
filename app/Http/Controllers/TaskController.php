<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskStatusHistory;
use App\Models\User;
use App\Services\GlobalIdeaSearchService;
use App\Services\TaskAttachmentService;
use App\Services\TaskReminderService;
use App\Services\TaskTreeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request, TaskTreeService $treeService): View
    {
        $this->authorize('viewAny', Task::class);
        $user = $request->user();
        $tab = $request->string('tab', 'all')->toString();

        $query = Task::query()
            ->with(['idea.user', 'creator', 'assignee', 'volunteers'])
            ->withCount(['subtasks', 'attachments'])
            ->where(function ($visible) use ($user, $tab): void {
                $visible
                    ->where('created_by_user_id', $user->id)
                    ->orWhere('assigned_to_user_id', $user->id)
                    ->orWhereHas('volunteers', fn ($volunteers) => $volunteers
                        ->where('user_id', $user->id)
                        ->where('status', 'approved'));

                if ($tab === 'community') {
                    $visible->orWhere(function ($community): void {
                        $community
                            ->where('participation_mode', 'open')
                            ->whereHas('idea', fn ($ideas) => $ideas->where('allow_task_collaboration', true));
                    });
                }
            })
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->orderBy('title')
            ->limit(500)
            ->get()
            ->filter(fn (Task $task) => $user->can('view', $task));

        $tasks = (match ($tab) {
            'today' => $query->filter(fn (Task $task) => $task->due_at?->isToday() && $task->status !== 'completada'),
            'upcoming' => $query->filter(fn (Task $task) => $task->due_at?->isFuture() && ! $task->due_at->isToday() && $task->status !== 'completada'),
            'no_date' => $query->filter(fn (Task $task) => ! $task->due_at && ! in_array($task->status, ['completada', 'cancelada'], true)),
            'completed' => $query->where('status', 'completada'),
            'community' => $query->filter(fn (Task $task) => $task->participation_mode === 'open'),
            default => $query->whereNotIn('status', ['completada', 'cancelada']),
        })->values();

        $tree = $treeService->prepare($tasks);

        $metrics = [
            'pending' => $query->whereNotIn('status', ['completada', 'cancelada'])->count(),
            'today' => $query->filter(fn (Task $task) => $task->due_at?->isToday() && $task->status !== 'completada')->count(),
            'overdue' => $query->filter(fn (Task $task) => $task->is_overdue)->count(),
            'completed' => $query->where('status', 'completada')->count(),
        ];

        return view('tasks.index', compact('tasks', 'tree', 'tab', 'metrics'));
    }

    public function create(Request $request, GlobalIdeaSearchService $ideaSearch): View
    {
        $this->authorize('create', Task::class);
        $user = $request->user();
        $ideaCandidates = $ideaSearch->accessibleCandidates($user, 0)
            ->filter(fn ($idea) => ! in_array($idea->workspace_status, ['completada', 'archivada', 'descartada'], true)
                && ! in_array($idea->status, ['archivada', 'descartada'], true)
                && ($user->can('update', $idea) || $idea->allow_task_collaboration))
            ->values();
        $selectedIdeaId = $ideaCandidates->contains('id', $request->integer('idea'))
            ? $request->integer('idea')
            : null;
        $parentCandidates = Task::query()
            ->active()
            ->with(['idea', 'creator'])
            ->orderBy('title')
            ->limit(300)
            ->get()
            ->filter(fn (Task $task) => $user->can('view', $task))
            ->values();
        $selectedParentId = $parentCandidates->contains('id', $request->integer('parent'))
            ? $request->integer('parent')
            : null;
        $assignableUsers = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('tasks.create', compact(
            'ideaCandidates', 'selectedIdeaId', 'parentCandidates', 'selectedParentId', 'assignableUsers'
        ));
    }

    public function store(
        StoreTaskRequest $request,
        TaskAttachmentService $attachments,
        TaskReminderService $reminders
    ): RedirectResponse {
        $task = DB::transaction(function () use ($request, $attachments, $reminders): Task {
            $parent = $request->filled('parent_task_id') ? Task::findOrFail($request->integer('parent_task_id')) : null;
            $ideaId = $request->filled('idea_id') ? $request->integer('idea_id') : $parent?->idea_id;
            $status = $request->input('status', 'pendiente');

            $task = Task::create([
                'created_by_user_id' => $request->user()->id,
                'assigned_to_user_id' => $request->filled('assigned_to_user_id')
                    ? $request->integer('assigned_to_user_id')
                    : $request->user()->id,
                'idea_id' => $ideaId,
                'parent_task_id' => $parent?->id,
                'title' => $request->string('title')->squish()->toString(),
                'description' => $request->filled('description') ? trim(strip_tags($request->input('description'))) : null,
                'status' => $status,
                'priority' => $request->input('priority'),
                'participation_mode' => $request->input('participation_mode'),
                'due_at' => $request->date('due_at'),
                'completed_at' => $status === 'completada' ? now() : null,
            ]);

            TaskStatusHistory::create([
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'new_status' => $status,
                'comment' => 'Tarea creada.',
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $attachments->store($task, $request->user(), $file);
            }

            $reminders->sync(
                $task,
                $request->user(),
                $request->input('remind_at'),
                $request->input('reminder_channels', []),
            );

            return $task;
        });

        return redirect()->route('tasks.show', $task)->with('success', 'Tarea creada correctamente.');
    }

    public function show(Task $task, TaskTreeService $treeService): View
    {
        $this->authorize('view', $task);
        $task->load([
            'creator', 'assignee', 'idea.user', 'parentTask', 'attachments.uploadedBy',
            'reminders', 'volunteers.user', 'volunteers.reviewedBy', 'statusHistories.user',
        ]);

        $root = $task;
        $visited = [];
        while ($root->parentTask && ! isset($visited[$root->id])) {
            $visited[$root->id] = true;
            $parent = $root->parentTask()->with('parentTask')->first();
            if (! $parent || ! request()->user()->can('view', $parent)) {
                break;
            }
            $root = $parent;
        }

        $treeTasks = collect([$root]);
        $pending = collect([$root->id]);
        while ($pending->isNotEmpty() && $treeTasks->count() < 250) {
            $level = Task::query()
                ->whereIn('parent_task_id', $pending)
                ->with(['idea', 'assignee'])
                ->orderBy('title')
                ->get()
                ->filter(fn (Task $node) => request()->user()->can('view', $node));
            $treeTasks = $treeTasks->concat($level)->unique('id')->values();
            $pending = $level->pluck('id');
        }
        $taskTree = $treeService->prepare($treeTasks);

        $reminder = $task->reminders->whereNull('sent_at')->sortBy('remind_at')->first();
        $reminderChannels = $task->reminders->whereNull('sent_at')->pluck('channel')->unique()->values();
        $assignableUsers = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('tasks.show', compact('task', 'taskTree', 'reminder', 'reminderChannels', 'assignableUsers'));
    }

    public function update(
        UpdateTaskRequest $request,
        Task $task,
        TaskReminderService $reminders
    ): RedirectResponse {
        DB::transaction(function () use ($request, $task, $reminders): void {
            $oldStatus = $task->status;
            $newStatus = $request->input('status');

            $task->update([
                'title' => $request->string('title')->squish()->toString(),
                'description' => $request->filled('description') ? trim(strip_tags($request->input('description'))) : null,
                'assigned_to_user_id' => $request->filled('assigned_to_user_id') ? $request->integer('assigned_to_user_id') : null,
                'status' => $newStatus,
                'priority' => $request->input('priority'),
                'participation_mode' => $request->input('participation_mode'),
                'due_at' => $request->date('due_at'),
                'completed_at' => $newStatus === 'completada' ? ($task->completed_at ?: now()) : null,
            ]);

            if ($oldStatus !== $newStatus) {
                TaskStatusHistory::create([
                    'task_id' => $task->id,
                    'user_id' => $request->user()->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'comment' => 'Estado actualizado desde la ficha de la tarea.',
                ]);
            }

            $reminders->sync(
                $task,
                $request->user(),
                $request->input('remind_at'),
                $request->input('reminder_channels', []),
            );
        });

        return back()->with('success', 'Tarea actualizada.');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $oldStatus = $task->status;
        $newStatus = $request->input('status');

        DB::transaction(function () use ($request, $task, $oldStatus, $newStatus): void {
            $task->update([
                'status' => $newStatus,
                'completed_at' => $newStatus === 'completada' ? now() : null,
            ]);
            if ($oldStatus !== $newStatus) {
                TaskStatusHistory::create([
                    'task_id' => $task->id,
                    'user_id' => $request->user()->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'comment' => 'Estado actualizado desde el listado.',
                ]);
            }
        });

        return back()->with('success', $newStatus === 'completada' ? 'Tarea completada.' : 'Estado de la tarea actualizado.');
    }

    public function destroy(Task $task, TaskTreeService $treeService): RedirectResponse
    {
        $this->authorize('delete', $task);
        $taskIds = $treeService->descendantIds($task)->push($task->id);
        $paths = TaskAttachment::query()->whereIn('task_id', $taskIds)->pluck('file_path');
        Storage::disk('local')->delete($paths->all());
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Tarea y subtareas eliminadas.');
    }
}
