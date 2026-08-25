<?php

namespace App\Http\Requests\Idea;

use App\Http\Requests\Concerns\ValidatesIdeaClassifications;
use App\Models\Idea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminUpdateIdeaRequest extends FormRequest
{
    use ValidatesIdeaClassifications;

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(Idea::COMMUNITY_STATUSES)],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'priority' => ['nullable', 'in:baja,media,alta,estrategica'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'classifications' => ['nullable', 'array', 'max:10'],
            'classifications.*' => ['nullable', 'array', 'max:20'],
            'classifications.*.*' => ['integer', 'distinct', 'exists:categories,id'],
            'admin_observations' => ['nullable', 'string', 'max:5000'],
            'next_action' => ['nullable', 'string', 'max:255'],
            'follow_up_date' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'status_comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        if (! $this->filled('category_id')) {
            return [];
        }

        return [fn (Validator $validator) => $this->validateIdeaClassifications($validator)];
    }
}
