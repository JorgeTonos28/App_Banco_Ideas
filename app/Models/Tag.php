<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function ideas(): BelongsToMany
    {
        return $this->belongsToMany(Idea::class, 'idea_tag');
    }

    public static function findOrCreateNormalized(string $name): self
    {
        return \App\Services\TagSimilarityService::findOrCreateNormalized($name);
    }

    public static function findSimilar(string $name, float $threshold = 0.70, int $limit = 5)
    {
        return \App\Services\TagSimilarityService::findSimilar($name, null, $threshold, $limit);
    }
}
