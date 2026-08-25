<?php

namespace App\Http\Requests\Idea;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIdeaHierarchyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $idea = $this->route('idea');

        return $idea && $this->user()?->can('organize', $idea);
    }

    public function rules(): array
    {
        return [
            'parent_idea_id' => ['nullable', 'integer', 'exists:ideas,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
