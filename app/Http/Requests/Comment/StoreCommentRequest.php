<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $idea = $this->route('idea');

        return auth()->check()
            && auth()->user()->is_active
            && $idea
            && $idea->isPublished()
            && auth()->user()->can('view', $idea);
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:2', 'max:2000'],
            'parent_id' => ['nullable', 'exists:idea_comments,id'],
        ];
    }
}
