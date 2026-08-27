<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeStatus', $this->route('task')) ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(Task::STATUSES)]];
    }
}
