<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_dimension_id',
        'parent_id',
        'slug',
        'icon',
        'color',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }

            if (empty($category->category_dimension_id)) {
                $category->category_dimension_id = CategoryDimension::query()
                    ->where('is_primary', true)
                    ->value('id');
            }
        });
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(CategoryDimension::class, 'category_dimension_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }

    public function classifiedIdeas(): BelongsToMany
    {
        return $this->belongsToMany(Idea::class, 'idea_category')->withTimestamps();
    }

    public function ancestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;
        $visited = [$this->id => true];

        while ($current && ! isset($visited[$current->id])) {
            $ancestors->prepend($current);
            $visited[$current->id] = true;
            $current = $current->parent;
        }

        return $ancestors;
    }

    public function getPathLabelAttribute(): string
    {
        return $this->ancestors()->push($this)->pluck('name')->implode(' / ');
    }
}
