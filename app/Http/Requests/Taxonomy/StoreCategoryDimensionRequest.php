<?php

namespace App\Http\Requests\Taxonomy;

use App\Models\CategoryDimension;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryDimensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:category_dimensions,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'selection_mode' => ['required', Rule::in(CategoryDimension::SELECTION_MODES)],
            'is_required' => ['nullable', 'boolean'],
            'is_hierarchical' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
