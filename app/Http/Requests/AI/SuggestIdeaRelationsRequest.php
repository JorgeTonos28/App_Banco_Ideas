<?php

namespace App\Http\Requests\AI;

use App\Models\Idea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SuggestIdeaRelationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_active;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:10000'],
            'problem_opportunity' => ['nullable', 'string', 'max:5000'],
            'parent_idea_id' => ['nullable', 'integer'],
            'current_idea_id' => ['nullable', 'integer'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (['parent_idea_id', 'current_idea_id'] as $field) {
                if ($this->filled($field) && ! Idea::query()
                    ->whereKey($this->integer($field))
                    ->where('user_id', $this->user()->id)
                    ->exists()) {
                    $validator->errors()->add($field, 'La idea seleccionada no está disponible para este asistente.');
                }
            }
        }];
    }
}
