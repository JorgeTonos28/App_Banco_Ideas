<?php

namespace App\Http\Requests\Idea;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportIdeaTreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('idea')) ?? false;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', Rule::in(['doc', 'json'])],
            'fields' => ['nullable', 'array', 'max:5'],
            'fields.*' => ['string', 'distinct', Rule::in(['description', 'problem_opportunity', 'tags', 'categories', 'relations'])],
            'all' => ['nullable', 'boolean'],
        ];
    }
}
