<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewTaskVolunteerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reviewVolunteers', $this->route('task')) ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['approved', 'rejected'])]];
    }
}
