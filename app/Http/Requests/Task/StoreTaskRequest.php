<?php

namespace App\Http\Requests\Task;

use App\Models\Idea;
use App\Models\Task;
use App\Models\TaskReminder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Task::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'idea_id' => ['nullable', 'integer', 'exists:ideas,id'],
            'parent_task_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(Task::STATUSES)],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'participation_mode' => ['required', Rule::in(Task::PARTICIPATION_MODES)],
            'due_at' => ['nullable', 'date'],
            'remind_at' => ['nullable', 'date', 'after:now'],
            'reminder_channels' => ['nullable', 'array', 'max:2'],
            'reminder_channels.*' => ['string', 'distinct', Rule::in(TaskReminder::CHANNELS)],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,ppt,pptx,zip,txt', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();
            $idea = $this->filled('idea_id') ? Idea::find($this->integer('idea_id')) : null;
            $parent = $this->filled('parent_task_id') ? Task::find($this->integer('parent_task_id')) : null;

            if ($idea && (! $user->can('view', $idea)
                || (! $user->can('update', $idea) && ! $idea->allow_task_collaboration)
                || in_array($idea->workspace_status, ['completada', 'archivada', 'descartada'], true)
                || in_array($idea->status, ['archivada', 'descartada'], true))) {
                $validator->errors()->add('idea_id', 'La idea seleccionada no acepta nuevas tareas.');
            }

            if ($parent && (! $user->can('view', $parent)
                || in_array($parent->status, ['completada', 'cancelada'], true)
                || ($idea && $parent->idea_id !== $idea->id)
                || (! $idea && $parent->idea_id))) {
                $validator->errors()->add('parent_task_id', 'La tarea superior no es compatible con esta ubicación.');
            }

            if ($this->input('participation_mode') === 'open'
                && (! $idea || ! $idea->allow_task_collaboration)) {
                $validator->errors()->add('participation_mode', 'La colaboración abierta requiere una idea que permita aportes.');
            }

            if ($this->filled('assigned_to_user_id')
                && ! $user->isAdmin()
                && $user->id !== $this->integer('assigned_to_user_id')
                && $idea
                && ! $user->can('update', $idea)) {
                $validator->errors()->add('assigned_to_user_id', 'No puedes asignar esta tarea a otra persona.');
            }

            if ($this->filled('remind_at') && $this->filled('due_at')
                && $this->date('remind_at')->isAfter($this->date('due_at'))) {
                $validator->errors()->add('remind_at', 'El recordatorio debe ocurrir antes del vencimiento.');
            }

            if ($this->filled('remind_at') xor $this->filled('reminder_channels')) {
                $validator->errors()->add('reminder_channels', 'Indica la fecha y al menos un canal para el recordatorio.');
            }
        }];
    }
}
