<?php

namespace App\Http\Requests\AI;

use App\Models\Idea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AnalyzeIdeaDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_active;
    }

    public function rules(): array
    {
        return [
            'transcript' => ['nullable', 'string', 'max:'.config('ai.limits.transcript_characters', 20000)],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'problem_opportunity' => ['nullable', 'string', 'max:5000'],
            'current_idea_id' => ['nullable', 'integer'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! collect(['transcript', 'title', 'description', 'problem_opportunity'])->contains(fn ($field) => filled($this->input($field)))) {
                $validator->errors()->add('transcript', 'Escribe o graba una idea antes de solicitar sugerencias.');
            }

            if ($this->filled('current_idea_id') && ! Idea::query()
                ->whereKey($this->integer('current_idea_id'))
                ->where('user_id', $this->user()->id)
                ->exists()) {
                $validator->errors()->add('current_idea_id', 'La idea actual no está disponible para este asistente.');
            }
        }];
    }
}
