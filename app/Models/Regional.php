<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Regional extends Model
{
    use HasFactory;

    public const TYPES = [
        'regional',
        'direction',
        'department',
    ];

    protected $fillable = [
        'parent_id',
        'type',
        'code',
        'name',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the full display name e.g. "DRM - Regional Metropolitana".
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'direction' => 'Dirección funcional',
            'department' => 'Departamento',
            default => 'Regional o sede',
        };
    }

    public function getCommunityNameAttribute(): string
    {
        return match ($this->type) {
            'direction' => "Comunidad de {$this->name}",
            'department' => "Comunidad de {$this->name}",
            default => "Comunidad {$this->name}",
        };
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order')->orderBy('name');
    }

    public function communityIdeas(): BelongsToMany
    {
        return $this->belongsToMany(Idea::class, 'idea_community_shares', 'organizational_unit_id', 'idea_id')
            ->withPivot(['include_descendants', 'shared_by_user_id'])
            ->withTimestamps();
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

    public function ancestorAndSelfIds(): Collection
    {
        return $this->ancestors()->pluck('id')->push($this->id)->values();
    }

    public function descendantIds(bool $includeSelf = false): Collection
    {
        $ids = $includeSelf ? collect([$this->id]) : collect();
        $pendingIds = collect([$this->id]);
        $visited = [$this->id => true];

        while ($pendingIds->isNotEmpty()) {
            $children = self::query()
                ->whereIn('parent_id', $pendingIds)
                ->get(['id', 'parent_id']);

            $newIds = $children->pluck('id')
                ->reject(fn (int $id) => isset($visited[$id]))
                ->values();

            foreach ($newIds as $id) {
                $visited[$id] = true;
            }

            $ids = $ids->concat($newIds);
            $pendingIds = $newIds;
        }

        return $ids->unique()->values();
    }

    public function path(): Collection
    {
        return $this->ancestors()->push($this);
    }

    public function getPathLabelAttribute(): string
    {
        return $this->path()->pluck('name')->implode(' / ');
    }

    public function isDescendantOf(self $unit): bool
    {
        return $this->ancestors()->contains(fn (self $ancestor) => $ancestor->is($unit));
    }

    /**
     * Users associated with this regional.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'regional_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'organizational_unit_id');
    }
}
