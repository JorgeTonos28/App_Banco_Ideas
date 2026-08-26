<?php

namespace App\Http\Requests\Idea;

use App\Http\Requests\Concerns\ValidatesIdeaAudience;
use App\Http\Requests\Concerns\ValidatesIdeaClassifications;
use App\Http\Requests\Concerns\ValidatesIdeaParent;
use App\Http\Requests\Concerns\ValidatesIdeaRelations;
use App\Models\Idea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateIdeaRequest extends FormRequest
{
    use ValidatesIdeaAudience;
    use ValidatesIdeaClassifications;
    use ValidatesIdeaParent;
    use ValidatesIdeaRelations;

    public function authorize(): bool
    {
        $idea = $this->route('idea');

        return $idea && auth()->user()->can('update', $idea);
    }

    public function rules(): array
    {
        return array_merge([
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
            'visibility' => ['required', 'in:private,draft,public'],
            'access_scope' => ['sometimes', 'required', Rule::in(Idea::ACCESS_SCOPES)],
            'organizational_unit_id' => ['nullable', 'integer', 'exists:regionals,id'],
            'include_descendants' => ['nullable', 'boolean'],
            'workspace_status' => ['nullable', Rule::in(Idea::WORKSPACE_STATUSES)],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,ppt,pptx,zip', 'max:10240'],
            'delete_attachments' => ['nullable', 'array'],
            'delete_attachments.*' => ['integer', 'exists:idea_attachments,id'],
        ], $this->ideaRelationRules());
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

            },
            fn (Validator $validator) => $this->validateIdeaAudience($validator),
            fn (Validator $validator) => $this->validateIdeaClassifications($validator),
            fn (Validator $validator) => $this->validateIdeaParent($validator, $this->route('idea')),
            fn (Validator $validator) => $this->validateIdeaRelations($validator, $this->route('idea')),
        ];
    }
}
