<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\StoreTaskAttachmentRequest;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\TaskAttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentController extends Controller
{
    public function store(
        StoreTaskAttachmentRequest $request,
        Task $task,
        TaskAttachmentService $service
    ): RedirectResponse {
        foreach ($request->file('attachments') as $file) {
            $service->store($task, $request->user(), $file);
        }

        return back()->with('success', 'Archivos agregados a la tarea.');
    }

    public function download(TaskAttachment $taskAttachment): StreamedResponse
    {
        $this->authorize('view', $taskAttachment->task);
        abort_unless(Storage::disk('local')->exists($taskAttachment->file_path), 404);

        return Storage::disk('local')->download(
            $taskAttachment->file_path,
            $taskAttachment->file_name,
            ['Content-Type' => $taskAttachment->mime_type, 'X-Content-Type-Options' => 'nosniff'],
        );
    }

    public function destroy(TaskAttachment $taskAttachment, TaskAttachmentService $service): RedirectResponse
    {
        $this->authorize('update', $taskAttachment->task);
        $service->delete($taskAttachment);

        return back()->with('success', 'Archivo eliminado.');
    }
}
