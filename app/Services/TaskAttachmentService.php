<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskAttachmentService
{
    public function store(Task $task, User $user, UploadedFile $file): TaskAttachment
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs('task_attachments/'.$task->id, $storedName, 'local');

        return $task->attachments()->create([
            'uploaded_by_user_id' => $user->id,
            'file_name' => Str::limit(basename($file->getClientOriginalName()), 255, ''),
            'file_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
        ]);
    }

    public function delete(TaskAttachment $attachment): void
    {
        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();
    }
}
