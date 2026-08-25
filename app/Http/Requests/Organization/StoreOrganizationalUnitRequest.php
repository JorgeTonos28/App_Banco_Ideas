<?php

namespace App\Http\Requests\Organization;

use App\Models\Regional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrganizationalUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'parent_id' => $this->input('type', 'regional') === 'regional'
                ? null
                : $this->input('parent_id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(Regional::TYPES)],
            'parent_id' => ['nullable', 'integer', 'exists:regionals,id'],
            'code' => ['required', 'string', 'max:20', 'unique:regionals,code'],
            'name' => ['required', 'string', 'max:100'],
            'order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateParentType($validator)];
    }

    private function validateParentType(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $type = $this->string('type')->toString();

        if ($type === 'regional') {
            return;
        }

        $parent = Regional::find($this->integer('parent_id'));
        $expectedParentType = $type === 'direction' ? 'regional' : 'direction';

        if (! $parent || $parent->type !== $expectedParentType) {
            $validator->errors()->add(
                'parent_id',
                $type === 'direction'
                    ? 'Una dirección funcional debe depender de una regional o sede.'
                    : 'Un departamento debe depender de una dirección funcional.'
            );
        }
    }
}
