<?php

namespace App\Http\Requests\Idea;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:nueva,en_revision,priorizada,en_desarrollo,implementada,descartada,archivada'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'priority' => ['nullable', 'in:baja,media,alta,estrategica'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'admin_observations' => ['nullable', 'string', 'max:5000'],
            'next_action' => ['nullable', 'string', 'max:255'],
            'follow_up_date' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'status_comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
