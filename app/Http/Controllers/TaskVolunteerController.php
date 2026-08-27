<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\ReviewTaskVolunteerRequest;
use App\Http\Requests\Task\VolunteerForTaskRequest;
use App\Models\Task;
use App\Models\TaskVolunteer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TaskVolunteerController extends Controller
{
    public function store(VolunteerForTaskRequest $request, Task $task): RedirectResponse
    {
        $task->volunteers()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['status' => 'pending', 'message' => $request->input('message'), 'reviewed_by_user_id' => null, 'reviewed_at' => null],
        );

        return back()->with('success', 'Tu solicitud fue enviada a la persona responsable.');
    }

    public function update(
        ReviewTaskVolunteerRequest $request,
        Task $task,
        TaskVolunteer $taskVolunteer
    ): RedirectResponse {
        abort_unless($taskVolunteer->task_id === $task->id, 404);

        DB::transaction(function () use ($request, $task, $taskVolunteer): void {
            $status = $request->input('status');
            $taskVolunteer->update([
                'status' => $status,
                'reviewed_by_user_id' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            if ($status === 'approved') {
                $task->update(['assigned_to_user_id' => $taskVolunteer->user_id]);
                $task->volunteers()
                    ->whereKeyNot($taskVolunteer->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'rejected', 'reviewed_by_user_id' => $request->user()->id, 'reviewed_at' => now()]);
            }
        });

        return back()->with('success', $request->input('status') === 'approved'
            ? 'Colaborador asignado a la tarea.'
            : 'Solicitud rechazada.');
    }
}
