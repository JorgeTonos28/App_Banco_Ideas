<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Idea extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'summary',
        'description',
        'problem_opportunity',
        'status',
        'visibility',
        'is_featured',
        'priority',
        'assigned_to_user_id',
        'admin_observations',
        'next_action',
        'follow_up_date',
        'views_count',
        'votes_count',
        'average_rating',
        'innovation_score',
        'implemented_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'average_rating' => 'float',
            'votes_count' => 'integer',
            'views_count' => 'integer',
            'innovation_score' => 'integer',
            'follow_up_date' => 'date',
            'implemented_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($idea) {
            if (empty($idea->slug)) {
                $baseSlug = Str::slug($idea->title);
                $slug = $baseSlug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-" . $count++;
                }
                $idea->slug = $slug;
            }

            if (empty($idea->summary) && !empty($idea->description)) {
                $idea->summary = Str::limit(strip_tags($idea->description), 160);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'idea_tag');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(IdeaAttachment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(IdeaRating::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IdeaComment::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(IdeaFavorite::class);
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'idea_favorites');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(IdeaStatusHistory::class)->orderBy('created_at', 'asc');
    }

    public function isEditableBy(?User $user): bool
    {
        if (!$user) return false;
        if ($user->isAdmin()) return true;
        return $this->user_id === $user->id && !in_array($this->status, ['implementada', 'descartada', 'archivada']);
    }

    public function isFavoritedBy(?User $user): bool
    {
        if (!$user) return false;
        return $this->favorites()->where('user_id', $user->id)->exists();
    }

    public function getUserRatingAttribute(): ?int
    {
        if (!auth()->check()) return null;
        $rating = $this->ratings()->where('user_id', auth()->id())->first();
        return $rating ? $rating->rating : null;
    }

    /**
     * Recalculates average rating and Innovation Score.
     * Innovation Score formula incorporates:
     * - Average Rating (0-5 -> scaled to 40%)
     * - Number of Votes (diminishing logarithmic return -> scaled to 30%)
     * - Community interaction (comments & views -> scaled to 15%)
     * - Recency / Freshness bonus (scaled to 15%)
     */
    public function recalculateRatingAndScore(): void
    {
        $votesCount = $this->ratings()->count();
        $avgRating = $votesCount > 0 ? (float) $this->ratings()->avg('rating') : 0.00;

        $commentsCount = $this->comments()->count();
        $viewsCount = $this->views_count;

        // Base rating score (0 - 40 points)
        $ratingScore = ($avgRating / 5.0) * 40;

        // Votes volume score (0 - 30 points)
        // 50 votes reaches approx max score
        $voteScore = min(30, ($votesCount / 25) * 30);

        // Community engagement score (0 - 15 points)
        $engagementScore = min(15, ($commentsCount * 1.5) + ($viewsCount * 0.05));

        // Freshness score (0 - 15 points)
        $daysOld = $this->created_at ? $this->created_at->diffInDays(now()) : 0;
        $freshnessScore = max(2, 15 - ($daysOld * 0.4));

        $totalScore = round($ratingScore + $voteScore + $engagementScore + $freshnessScore);
        $totalScore = min(100, max(0, $totalScore));

        $this->updateQuietly([
            'votes_count' => $votesCount,
            'average_rating' => round($avgRating, 2),
            'innovation_score' => (int) $totalScore,
        ]);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'nueva' => 'Nueva',
            'en_revision' => 'En revisión',
            'priorizada' => 'Priorizada',
            'en_desarrollo' => 'En desarrollo',
            'implementada' => 'Implementada',
            'descartada' => 'Descartada',
            'archivada' => 'Archivada',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusBadgeClassesAttribute(): array
    {
        return match ($this->status) {
            'nueva' => [
                'bg' => 'bg-surface-container-high',
                'text' => 'text-on-surface-variant',
                'border' => 'border-outline-variant',
                'icon' => 'lightbulb',
            ],
            'en_revision' => [
                'bg' => 'bg-secondary-container/20',
                'text' => 'text-secondary',
                'border' => 'border-secondary/30',
                'icon' => 'visibility',
            ],
            'priorizada' => [
                'bg' => 'bg-primary-fixed',
                'text' => 'text-on-primary-fixed-variant',
                'border' => 'border-primary/30',
                'icon' => 'star',
            ],
            'en_desarrollo' => [
                'bg' => 'bg-tertiary-fixed',
                'text' => 'text-on-tertiary-fixed-variant',
                'border' => 'border-tertiary/30',
                'icon' => 'science',
            ],
            'implementada' => [
                'bg' => 'bg-emerald-100 text-emerald-800',
                'text' => 'text-emerald-800',
                'border' => 'border-emerald-300',
                'icon' => 'rocket_launch',
            ],
            'descartada' => [
                'bg' => 'bg-error-container',
                'text' => 'text-on-error-container',
                'border' => 'border-error/30',
                'icon' => 'block',
            ],
            'archivada' => [
                'bg' => 'bg-slate-200 text-slate-700',
                'text' => 'text-slate-700',
                'border' => 'border-slate-300',
                'icon' => 'inventory_2',
            ],
            default => [
                'bg' => 'bg-surface-container',
                'text' => 'text-on-surface',
                'border' => 'border-outline',
                'icon' => 'help',
            ],
        };
    }
}
