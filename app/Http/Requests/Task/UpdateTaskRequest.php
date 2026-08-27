<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use App\Models\TaskReminder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('task')) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in(Task::STATUSES)],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'participation_mode' => ['required', Rule::in(Task::PARTICIPATION_MODES)],
            'due_at' => ['nullable', 'date'],
            'remind_at' => ['nullable', 'date', 'after:now'],
            'reminder_channels' => ['nullable', 'array', 'max:2'],
            'reminder_channels.*' => ['string', 'distinct', Rule::in(TaskReminder::CHANNELS)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Task $task */
            $task = $this->route('task');
            if ($this->input('participation_mode') === 'open'
                && (! $task->idea || ! $task->idea->allow_task_collaboration)) {
                $validator->errors()->add('participation_mode', 'La idea vinculada no permite colaboración abierta.');
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
