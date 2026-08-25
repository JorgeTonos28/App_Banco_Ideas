<?php

namespace App\Http\Requests\Organization;

use App\Models\Regional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOrganizationalUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'parent_id' => $this->input('type') === 'regional' ? null : $this->input('parent_id'),
        ]);
    }

    public function rules(): array
    {
        $unit = $this->route('regional');

        return [
            'type' => ['required', Rule::in(Regional::TYPES)],
            'parent_id' => ['nullable', 'integer', 'exists:regionals,id'],
            'code' => ['required', 'string', 'max:20', Rule::unique('regionals', 'code')->ignore($unit)],
            'name' => ['required', 'string', 'max:100'],
            'order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateHierarchy($validator)];
    }

    private function validateHierarchy(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        /** @var Regional $unit */
        $unit = $this->route('regional');
        $type = $this->string('type')->toString();
        $parent = $this->filled('parent_id') ? Regional::find($this->integer('parent_id')) : null;

        if ($parent && ($parent->is($unit) || $parent->isDescendantOf($unit))) {
            $validator->errors()->add('parent_id', 'La unidad seleccionada crearía un ciclo en la estructura.');

            return;
        }

        $expectedParentType = match ($type) {
            'direction' => 'regional',
            'department' => 'direction',
            default => null,
        };

        if (($expectedParentType && $parent?->type !== $expectedParentType) || (! $expectedParentType && $parent)) {
            $validator->errors()->add('parent_id', 'El nivel superior no corresponde al tipo de unidad seleccionado.');
        }

        $allowedChildType = match ($type) {
            'regional' => 'direction',
            'direction' => 'department',
            default => null,
        };

        $hasInvalidChildren = $allowedChildType
            ? $unit->children()->where('type', '!=', $allowedChildType)->exists()
            : $unit->children()->exists();

        if ($hasInvalidChildren) {
            $validator->errors()->add('type', 'Reubica primero las unidades dependientes antes de cambiar este tipo.');
        }
    }
}
