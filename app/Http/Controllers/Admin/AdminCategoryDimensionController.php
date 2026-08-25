<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Taxonomy\DeleteCategoryDimensionRequest;
use App\Http\Requests\Taxonomy\StoreCategoryDimensionRequest;
use App\Http\Requests\Taxonomy\UpdateCategoryDimensionRequest;
use App\Models\CategoryDimension;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class AdminCategoryDimensionController extends Controller
{
    public function store(StoreCategoryDimensionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $data['is_required'] = $request->boolean('is_required');
        $data['is_hierarchical'] = $request->boolean('is_hierarchical');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_primary'] = false;

        CategoryDimension::create($data);

        return back()->with('success', 'La dimensión de clasificación fue creada.');
    }

    public function update(UpdateCategoryDimensionRequest $request, CategoryDimension $categoryDimension): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $data['is_required'] = $categoryDimension->is_primary || $request->boolean('is_required');
        $data['is_hierarchical'] = $request->boolean('is_hierarchical');
        $data['is_active'] = $categoryDimension->is_primary || $request->boolean('is_active');
        $data['selection_mode'] = $categoryDimension->is_primary ? 'single' : $data['selection_mode'];

        $categoryDimension->update($data);

        return back()->with('success', 'La dimensión de clasificación fue actualizada.');
    }

    public function destroy(DeleteCategoryDimensionRequest $request, CategoryDimension $categoryDimension): RedirectResponse
    {
        if ($categoryDimension->is_primary) {
            return back()->with('error', 'La dimensión principal del sistema no puede eliminarse.');
        }

        if ($categoryDimension->categories()->exists()) {
            return back()->with('error', 'Elimina o reasigna primero las categorías de esta dimensión.');
        }

        $categoryDimension->delete();

        return back()->with('success', 'La dimensión fue eliminada.');
    }
}
