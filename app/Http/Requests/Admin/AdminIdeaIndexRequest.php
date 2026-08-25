<?php

namespace App\Http\Requests\Admin;

use App\Models\Idea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminIdeaIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'parent' => ['nullable', 'integer', 'exists:ideas,id'],
            'estado' => ['nullable', Rule::in(Idea::COMMUNITY_STATUSES)],
            'publicacion' => ['nullable', Rule::in(Idea::PUBLICATION_STATUSES)],
            'categoria' => ['nullable', 'integer', 'exists:categories,id'],
            'prioridad' => ['nullable', Rule::in(['baja', 'media', 'alta', 'estrategica'])],
            'responsable' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
