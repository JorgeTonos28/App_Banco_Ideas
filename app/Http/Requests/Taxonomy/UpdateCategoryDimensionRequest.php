<?php

namespace App\Http\Requests\Taxonomy;

use App\Models\CategoryDimension;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryDimensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $dimension = $this->route('categoryDimension');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('category_dimensions', 'name')->ignore($dimension)],
            'description' => ['nullable', 'string', 'max:1000'],
            'selection_mode' => ['required', Rule::in(CategoryDimension::SELECTION_MODES)],
            'is_required' => ['nullable', 'boolean'],
            'is_hierarchical' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var CategoryDimension $dimension */
                $dimension = $this->route('categoryDimension');

                if ($dimension->is_primary
                    && ($this->input('selection_mode') !== 'single'
                        || ! $this->boolean('is_required')
                        || ! $this->boolean('is_active'))) {
                    $validator->errors()->add('selection_mode', 'La dimensión principal debe permanecer activa, obligatoria y de selección única.');
                }

                if (! $this->boolean('is_hierarchical') && $dimension->categories()->whereNotNull('parent_id')->exists()) {
                    $validator->errors()->add('is_hierarchical', 'Elimina primero las relaciones padre-hijo de esta dimensión.');
                }

                if ($dimension->selection_mode === 'multiple' && $this->input('selection_mode') === 'single') {
                    $hasMultipleSelections = DB::table('idea_category')
                        ->join('categories', 'categories.id', '=', 'idea_category.category_id')
                        ->where('categories.category_dimension_id', $dimension->id)
                        ->groupBy('idea_category.idea_id')
                        ->havingRaw('COUNT(*) > 1')
                        ->exists();

                    if ($hasMultipleSelections) {
                        $validator->errors()->add('selection_mode', 'Hay ideas con varias selecciones en esta dimensión. Reorganízalas antes de cambiar el modo.');
                    }
                }
            },
        ];
    }
}
