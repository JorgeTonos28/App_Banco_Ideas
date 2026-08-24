<?php

namespace App\Http\Requests\Idea;

use Illuminate\Foundation\Http\FormRequest;

class DeleteIdeaRelationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $relation = $this->route('ideaRelation');

        return $relation && $this->user()?->can('delete', $relation);
    }

    public function rules(): array
    {
        return [];
    }
}
