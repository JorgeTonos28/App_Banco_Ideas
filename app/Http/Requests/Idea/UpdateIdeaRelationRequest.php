<?php

namespace App\Http\Requests\Idea;

use App\Models\IdeaRelation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIdeaRelationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $relation = $this->route('ideaRelation');

        return $relation && $this->user()?->can('update', $relation);
    }

    public function rules(): array
    {
        /** @var IdeaRelation $relation */
        $relation = $this->route('ideaRelation');

        return [
            'type' => [
                'required',
                Rule::in(IdeaRelation::TYPES),
                Rule::unique('idea_relations', 'type')
                    ->where(fn ($query) => $query
                        ->where('source_idea_id', $relation->source_idea_id)
                        ->where('target_idea_id', $relation->target_idea_id))
                    ->ignore($relation->id),
            ],
            'rationale' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
