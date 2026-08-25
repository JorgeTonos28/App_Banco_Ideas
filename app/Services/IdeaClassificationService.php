<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CategoryDimension;
use App\Models\Idea;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class IdeaClassificationService
{
    public function sync(Idea $idea, array $classifications, int $primaryCategoryId): void
    {
        $idea->categories()->sync(
            $this->normalize($classifications, $primaryCategoryId)->all()
        );
    }

    public function normalize(array $classifications, int $primaryCategoryId): Collection
    {
        $selections = collect($classifications)
            ->mapWithKeys(function ($values, $dimensionId): array {
                $ids = collect(is_array($values) ? $values : [$values])
                    ->filter(fn ($value) => filter_var($value, FILTER_VALIDATE_INT) !== false)
                    ->map(fn ($value) => (int) $value)
                    ->filter()
                    ->unique()
                    ->values();

                return [(int) $dimensionId => $ids];
            })
            ->filter(fn (Collection $ids, int $dimensionId) => $dimensionId > 0 && $ids->isNotEmpty());

        $primaryCategory = Category::query()
            ->where('is_active', true)
            ->with('dimension')
            ->find($primaryCategoryId);

        if (! $primaryCategory || ! $primaryCategory->dimension?->is_active || ! $primaryCategory->dimension->is_primary) {
            throw ValidationException::withMessages([
                'category_id' => 'Selecciona una categoría principal activa.',
            ]);
        }

        $primaryDimensionId = $primaryCategory->category_dimension_id;
        $selections->put($primaryDimensionId, collect([$primaryCategory->id]));

        $dimensions = CategoryDimension::query()
            ->active()
            ->with(['categories' => fn ($query) => $query->where('is_active', true)])
            ->get()
            ->keyBy('id');

        foreach ($selections as $dimensionId => $categoryIds) {
            $dimension = $dimensions->get($dimensionId);

            if (! $dimension) {
                throw ValidationException::withMessages([
                    'classifications' => 'Una de las dimensiones seleccionadas no está disponible.',
                ]);
            }

            $validIds = $dimension->categories->pluck('id');
            if ($categoryIds->diff($validIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    "classifications.{$dimensionId}" => "Hay valores que no pertenecen a la dimensión {$dimension->name}.",
                ]);
            }

            if ($dimension->selection_mode === 'single' && $categoryIds->count() > 1) {
                throw ValidationException::withMessages([
                    "classifications.{$dimensionId}" => "La dimensión {$dimension->name} permite una sola selección.",
                ]);
            }
        }

        foreach ($dimensions as $dimension) {
            $hasAvailableCategories = $dimension->categories->isNotEmpty();
            if ($dimension->is_required
                && $hasAvailableCategories
                && $selections->get($dimension->id, collect())->isEmpty()) {
                throw ValidationException::withMessages([
                    "classifications.{$dimension->id}" => "Selecciona al menos un valor para {$dimension->name}.",
                ]);
            }
        }

        return $selections->flatten()->unique()->values();
    }

    public function currentSelections(Idea $idea): array
    {
        return $idea->categories
            ->groupBy('category_dimension_id')
            ->map(fn (Collection $categories) => $categories->pluck('id')->all())
            ->all();
    }
}
