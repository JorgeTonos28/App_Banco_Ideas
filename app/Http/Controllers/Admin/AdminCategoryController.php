<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Taxonomy\DeleteCategoryRequest;
use App\Http\Requests\Taxonomy\StoreCategoryRequest;
use App\Http\Requests\Taxonomy\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\CategoryDimension;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function index(): View
    {
        $dimensions = CategoryDimension::ordered()
            ->with(['categories' => fn ($query) => $query
                ->with(['parent'])
                ->withCount(['ideas', 'classifiedIdeas', 'children'])])
            ->get();
        $categories = $dimensions->flatMap->categories;

        return view('admin.categories.index', compact('categories', 'dimensions'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        Category::create($validated);

        return back()->with('success', 'Categoría creada exitosamente.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        return back()->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(DeleteCategoryRequest $request, Category $category): RedirectResponse
    {
        if ($category->ideas()->exists() || $category->classifiedIdeas()->exists()) {
            return back()->with('error', 'No se puede eliminar la categoría porque tiene ideas asociadas.');
        }

        if ($category->children()->exists()) {
            return back()->with('error', 'Reubica primero las categorías dependientes.');
        }

        $category->delete();

        return back()->with('success', 'Categoría eliminada con éxito.');
    }
}
