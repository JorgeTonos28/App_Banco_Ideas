<?php

namespace App\Http\Requests\Idea;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewIdeaRelationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $relation = $this->route('ideaRelation');

        return $relation && $this->user()?->can('review', $relation);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ];
    }
}
