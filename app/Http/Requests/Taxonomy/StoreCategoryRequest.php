<?php

namespace App\Http\Requests\Taxonomy;

use App\Models\Category;
use App\Models\CategoryDimension;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'category_dimension_id' => ['required', 'integer', 'exists:category_dimensions,id'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'icon' => ['required', 'string', 'regex:/^[a-z0-9_]{1,50}$/'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateParent($validator)];
    }

    protected function validateParent(Validator $validator, ?Category $category = null): void
    {
        if ($validator->errors()->isNotEmpty() || ! $this->filled('parent_id')) {
            return;
        }

        $dimension = CategoryDimension::find($this->integer('category_dimension_id'));
        $parent = Category::find($this->integer('parent_id'));

        if (! $dimension?->is_hierarchical) {
            $validator->errors()->add('parent_id', 'Esta dimensión no admite categorías anidadas.');
        }

        if ($parent?->category_dimension_id !== $dimension?->id) {
            $validator->errors()->add('parent_id', 'La categoría superior debe pertenecer a la misma dimensión.');
        }

        if ($category && $parent?->is($category)) {
            $validator->errors()->add('parent_id', 'Una categoría no puede ser su propia categoría superior.');
        }
    }
}
