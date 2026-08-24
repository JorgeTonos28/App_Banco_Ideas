<?php

namespace App\Http\Requests\Idea;

use App\Models\Idea;
use App\Models\IdeaRelation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreIdeaRelationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $idea = $this->route('idea');

        return $idea && $this->user()?->can('organize', $idea);
    }

    public function rules(): array
    {
        return [
            'target_idea_id' => ['required', 'integer', 'exists:ideas,id'],
            'type' => ['required', Rule::in(IdeaRelation::TYPES)],
            'rationale' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $target = Idea::find($this->integer('target_idea_id'));

                if (! $target || ! $this->user()?->can('view', $target)) {
                    $validator->errors()->add('target_idea_id', 'No tienes acceso a la idea relacionada seleccionada.');
                }
            },
        ];
    }
}
