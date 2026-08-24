<?php

namespace App\Http\Requests\Taxonomy;

use App\Models\Category;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'category_dimension_id' => ['required', 'integer', 'exists:category_dimensions,id'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category)],
            'icon' => ['required', 'string', 'regex:/^[a-z0-9_]{1,50}$/'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Category $category */
                $category = $this->route('category');
                $this->validateParent($validator, $category);

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $isUsed = $category->ideas()->exists() || $category->classifiedIdeas()->exists();

                if ($isUsed && $category->category_dimension_id !== $this->integer('category_dimension_id')) {
                    $validator->errors()->add('category_dimension_id', 'No puedes mover una categoría usada por ideas a otra dimensión.');
                }

                if ($isUsed && ! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'No puedes desactivar una categoría mientras esté asociada a ideas.');
                }

                $parent = $this->filled('parent_id') ? Category::find($this->integer('parent_id')) : null;
                $visited = [];
                while ($parent) {
                    if ($parent->is($category) || isset($visited[$parent->id])) {
                        $validator->errors()->add('parent_id', 'El cambio crearía un ciclo en la jerarquía de categorías.');
                        break;
                    }

                    $visited[$parent->id] = true;
                    $parent = $parent->parent;
                }
            },
        ];
    }
}
