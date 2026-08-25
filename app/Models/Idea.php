<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Idea extends Model
{
    use HasFactory;

    public const ACCESS_SCOPES = [
        'only_me',
        'profile',
        'organization',
    ];

    public const WORKSPACE_STATUSES = [
        'capturada',
        'en_clarificacion',
        'lista_para_actuar',
        'en_ejecucion',
        'completada',
        'en_pausa',
        'descartada',
        'archivada',
    ];

    public const COMMUNITY_STATUSES = [
        'nueva',
        'en_revision',
        'priorizada',
        'en_desarrollo',
        'implementada',
        'descartada',
        'archivada',
    ];

    public const PUBLICATION_STATUSES = [
        'not_submitted',
        'pending_review',
        'changes_requested',
        'published',
        'rejected',
        'unpublished',
    ];

    public const PUBLICATION_REQUESTABLE_STATUSES = [
        'not_submitted',
        'changes_requested',
        'rejected',
        'unpublished',
    ];

    public const PUBLICATION_REVIEW_STATUSES = [
        'changes_requested',
        'published',
        'rejected',
        'unpublished',
    ];

    public const COMMUNITY_DISPLAY_MODES = [
        'standalone',
        'represented_by_parent',
        'hidden',
    ];

    protected $fillable = [
        'user_id',
        'category_id',
        'parent_idea_id',
        'title',
        'slug',
        'summary',
        'description',
        'problem_opportunity',
        'status',
        'visibility',
        'access_scope',
        'workspace_status',
        'publication_status',
        'community_display',
        'requested_community_display',
        'publication_requested_at',
        'publication_requested_by_user_id',
        'publication_reviewed_at',
        'publication_reviewed_by_user_id',
        'published_at',
        'publication_notes',
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
            'publication_requested_at' => 'datetime',
            'publication_reviewed_at' => 'datetime',
            'published_at' => 'datetime',
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
                    $slug = "{$baseSlug}-".$count++;
                }
                $idea->slug = $slug;
            }

            if (empty($idea->summary) && ! empty($idea->description)) {
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

    public function publicationRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publication_requested_by_user_id');
    }

    public function publicationReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publication_reviewed_by_user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'idea_category')->withTimestamps();
    }

    public function communityUnits(): BelongsToMany
    {
        return $this->belongsToMany(Regional::class, 'idea_community_shares', 'idea_id', 'organizational_unit_id')
            ->withPivot(['include_descendants', 'shared_by_user_id'])
            ->withTimestamps();
    }

    public function parentIdea(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_idea_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_idea_id')->orderByDesc('updated_at');
    }

    public function outgoingRelations(): HasMany
    {
        return $this->hasMany(IdeaRelation::class, 'source_idea_id');
    }

    public function incomingRelations(): HasMany
    {
        return $this->hasMany(IdeaRelation::class, 'target_idea_id');
    }

    public function hierarchyHistories(): HasMany
    {
        return $this->hasMany(IdeaHierarchyHistory::class)->orderBy('created_at');
    }

    public function ancestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parentIdea;
        $visited = [$this->id => true];

        while ($current && ! isset($visited[$current->id])) {
            $ancestors->prepend($current);
            $visited[$current->id] = true;
            $current = $current->parentIdea;
        }

        return $ancestors;
    }

    public function rootIdea(): self
    {
        return $this->ancestors()->first() ?: $this;
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

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publication_status', 'published');
    }

    public function scopeCommunityPublished(Builder $query): Builder
    {
        return $query
            ->published()
            ->where('community_display', 'standalone');
    }

    public function scopeVisibleOnProfile(Builder $query): Builder
    {
        return $query->visibleOnProfileFor(auth()->user());
    }

    public function scopeVisibleOnProfileFor(Builder $query, ?User $viewer): Builder
    {
        return $query
            ->whereNull('parent_idea_id')
            ->where(function (Builder $visible) use ($viewer): void {
                $visible
                    ->where('publication_status', 'published')
                    ->orWhere(function (Builder $shared): void {
                        $shared
                            ->where('access_scope', 'profile')
                            ->where('visibility', '!=', 'draft');
                    });

                if ($viewer?->isAdmin()) {
                    $visible->orWhere(function (Builder $internal): void {
                        $internal
                            ->where('access_scope', 'organization')
                            ->where('visibility', '!=', 'draft')
                            ->whereHas('communityUnits');
                    });

                    return;
                }

                $viewerUnit = $viewer?->effectiveOrganizationalUnit();

                if ($viewerUnit) {
                    $viewerPathIds = $viewerUnit->ancestorAndSelfIds();

                    $visible->orWhere(function (Builder $internal) use ($viewerUnit, $viewerPathIds): void {
                        $internal
                            ->where('access_scope', 'organization')
                            ->where('visibility', '!=', 'draft')
                            ->whereHas('communityUnits', function (Builder $shares) use ($viewerUnit, $viewerPathIds): void {
                                $shares
                                    ->where('regionals.id', $viewerUnit->id)
                                    ->orWhere(function (Builder $inherited) use ($viewerPathIds): void {
                                        $inherited
                                            ->whereIn('regionals.id', $viewerPathIds)
                                            ->where('idea_community_shares.include_descendants', true);
                                    });
                            });
                    });
                }
            });
    }

    public function isPublished(): bool
    {
        return $this->publication_status === 'published';
    }

    public function isPublishedToCommunity(): bool
    {
        return $this->isPublished() && $this->community_display === 'standalone';
    }

    public function usesCommunityLifecycle(): bool
    {
        return $this->isPublished();
    }

    public function canRequestPublication(): bool
    {
        return $this->visibility !== 'draft'
            && in_array($this->publication_status, self::PUBLICATION_REQUESTABLE_STATUSES, true);
    }

    public function isSharedOnProfile(): bool
    {
        return in_array($this->access_scope, ['profile', 'organization'], true)
            && $this->visibility !== 'draft';
    }

    public function isAccessibleToAuthenticatedAudience(?User $viewer = null): bool
    {
        if ($this->isPublished()) {
            return true;
        }

        if (! $this->isSharedOnProfile()) {
            return false;
        }

        $chain = $this->ancestors()->push($this);

        if ($chain->contains(fn (Idea $node) => ! $node->isPublished() && ! $node->isSharedOnProfile())) {
            return false;
        }

        if ($chain->contains(fn (Idea $node) => ! $node->isPublished() && $node->access_scope === 'organization')) {
            $audienceIdea = $chain->first(fn (Idea $node) => ! $node->isPublished()) ?: $this;

            return $viewer ? $audienceIdea->isSharedWithOrganization($viewer) : false;
        }

        return true;
    }

    public function isSharedWithOrganization(User $viewer): bool
    {
        $unit = $viewer->effectiveOrganizationalUnit();

        if (! $unit || $this->access_scope !== 'organization' || $this->visibility === 'draft') {
            return false;
        }

        $viewerPathIds = $unit->ancestorAndSelfIds();

        return $this->communityUnits()
            ->where(function (Builder $shares) use ($unit, $viewerPathIds): void {
                $shares
                    ->where('regionals.id', $unit->id)
                    ->orWhere(function (Builder $descendantShares) use ($viewerPathIds): void {
                        $descendantShares
                            ->whereIn('regionals.id', $viewerPathIds)
                            ->where('idea_community_shares.include_descendants', true);
                    });
            })
            ->exists();
    }

    public function acceptsRatings(): bool
    {
        if ($this->parent_idea_id !== null) {
            return false;
        }

        if ($this->isPublished()) {
            return $this->isPublishedToCommunity()
                && ! in_array($this->status, ['descartada', 'archivada'], true);
        }

        return $this->isAccessibleToAuthenticatedAudience(auth()->user())
            && ! in_array($this->workspace_status, ['descartada', 'archivada'], true);
    }

    public function hasPreliminaryRatings(): bool
    {
        return ! $this->isPublished() && $this->acceptsRatings();
    }

    public function isEditableBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($this->user_id !== $user->id) {
            return false;
        }

        return $this->isPublished()
            ? ! in_array($this->status, ['implementada', 'descartada', 'archivada'], true)
            : ! in_array($this->workspace_status, ['descartada', 'archivada'], true);
    }

    public function isFavoritedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->favorites()->where('user_id', $user->id)->exists();
    }

    public function getUserRatingAttribute(): ?int
    {
        if (! auth()->check()) {
            return null;
        }

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

        $totalScore = 0;

        if ($this->isPublishedToCommunity()) {
            $totalScore = round($ratingScore + $voteScore + $engagementScore + $freshnessScore);
            $totalScore = min(100, max(0, $totalScore));
        }

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

    public function getWorkspaceStatusLabelAttribute(): string
    {
        return match ($this->workspace_status) {
            'capturada' => 'Capturada',
            'en_clarificacion' => 'En clarificación',
            'lista_para_actuar' => 'Lista para actuar',
            'en_ejecucion' => 'En ejecución',
            'completada' => 'Completada',
            'en_pausa' => 'En pausa',
            'descartada' => 'Descartada',
            'archivada' => 'Archivada',
            default => ucfirst(str_replace('_', ' ', $this->workspace_status)),
        };
    }

    public function getPublicationStatusLabelAttribute(): string
    {
        return match ($this->publication_status) {
            'not_submitted' => 'No enviada',
            'pending_review' => 'Pendiente de revisión',
            'changes_requested' => 'Cambios solicitados',
            'published' => 'Publicada',
            'rejected' => 'Rechazada',
            'unpublished' => 'Retirada',
            default => ucfirst(str_replace('_', ' ', $this->publication_status)),
        };
    }

    public function getAccessScopeLabelAttribute(): string
    {
        return match ($this->access_scope) {
            'profile' => 'Visible en mi perfil',
            'organization' => 'Compartida en comunidad interna',
            default => 'Sólo yo',
        };
    }

    public function getRequestedCommunityDisplayLabelAttribute(): string
    {
        return $this->requested_community_display === 'represented_by_parent'
            ? 'Subidea, representada dentro de su madre'
            : 'Idea principal, crea una tarjeta';
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
