<?php

namespace App\Http\Requests\Idea;

use App\Models\Idea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewIdeaPublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $idea = $this->route('idea');

        return $idea && $this->user()?->can('reviewPublication', $idea);
    }

    public function rules(): array
    {
        return [
            'publication_status' => ['required', Rule::in(Idea::PUBLICATION_REVIEW_STATUSES)],
            'publication_notes' => [
                Rule::requiredIf(in_array($this->input('publication_status'), ['changes_requested', 'rejected'], true)),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
