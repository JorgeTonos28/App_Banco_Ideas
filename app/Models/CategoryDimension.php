<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CategoryDimension extends Model
{
    use HasFactory;

    public const SELECTION_MODES = ['single', 'multiple'];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'selection_mode',
        'is_required',
        'is_hierarchical',
        'is_primary',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_hierarchical' => 'boolean',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CategoryDimension $dimension): void {
            if (empty($dimension->slug)) {
                $dimension->slug = Str::slug($dimension->name);
            }
        });
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order')->orderBy('name');
    }

    public function rootCategories(): HasMany
    {
        return $this->categories()->whereNull('parent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getSelectionModeLabelAttribute(): string
    {
        return $this->selection_mode === 'single' ? 'Selección única' : 'Selección múltiple';
    }
}
