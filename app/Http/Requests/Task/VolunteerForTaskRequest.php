<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class VolunteerForTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('volunteer', $this->route('task')) ?? false;
    }

    public function rules(): array
    {
        return ['message' => ['nullable', 'string', 'max:1000']];
    }
}
