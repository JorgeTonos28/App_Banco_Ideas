<?php

namespace App\Http\Requests\Idea;

use App\Http\Requests\Concerns\ValidatesIdeaClassifications;
use App\Http\Requests\Concerns\ValidatesIdeaParent;
use App\Models\Idea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreIdeaRequest extends FormRequest
{
    use ValidatesIdeaClassifications;
    use ValidatesIdeaParent;

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_active;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:10000'],
            'problem_opportunity' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'exists:categories,id'],
            'parent_idea_id' => ['nullable', 'integer', 'exists:ideas,id'],
            'classifications' => ['nullable', 'array', 'max:10'],
            'classifications.*' => ['nullable', 'array', 'max:20'],
            'classifications.*.*' => ['integer', 'distinct', 'exists:categories,id'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:500'],
            'visibility' => ['required', 'in:private,draft'],
            'access_scope' => ['sometimes', 'required', Rule::in(Idea::ACCESS_SCOPES)],
            'workspace_status' => ['nullable', Rule::in(Idea::WORKSPACE_STATUSES)],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,ppt,pptx,zip', 'max:10240'], // 10MB max
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $tagNames = collect($this->input('tags', []))
                    ->flatMap(fn (string $item) => explode(',', $item))
                    ->map(fn (string $item) => trim(ltrim(trim($item), '#')))
                    ->filter();

                if ($tagNames->count() > 20) {
                    $validator->errors()->add('tags', 'Puedes asociar un máximo de 20 etiquetas.');
                }

                if ($tagNames->contains(fn (string $name) => mb_strlen($name) > 50)) {
                    $validator->errors()->add('tags', 'Cada etiqueta puede tener un máximo de 50 caracteres.');
                }

                if ($this->input('visibility') === 'draft' && $this->input('access_scope', 'only_me') === 'profile') {
                    $validator->errors()->add('access_scope', 'Completa la idea antes de hacerla visible en tu perfil.');
                }
            },
            fn (Validator $validator) => $this->validateIdeaClassifications($validator),
            fn (Validator $validator) => $this->validateIdeaParent($validator),
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título de la idea es obligatorio.',
            'title.min' => 'El título debe tener al menos 5 caracteres.',
            'description.required' => 'La descripción de la propuesta es obligatoria.',
            'description.min' => 'Describe tu idea con al menos 20 caracteres.',
            'category_id.required' => 'Debes seleccionar una categoría.',
            'category_id.exists' => 'La categoría seleccionada no es válida.',
            'attachments.*.max' => 'Los archivos adjuntos no deben superar los 10 MB.',
            'attachments.*.mimes' => 'Formato de archivo no permitido. Solo se aceptan PDF, documentos de oficina e imágenes.',
        ];
    }
}
