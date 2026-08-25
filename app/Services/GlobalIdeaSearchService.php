<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GlobalIdeaSearchService
{
    public function accessibleCandidates(User $viewer, int $exceptIdeaId, int $limit = 250): Collection
    {
        $query = Idea::query()
            ->whereKeyNot($exceptIdeaId)
            ->with(['user', 'category'])
            ->orderBy('title');

        $this->applyAccessibleCandidateScope($query, $viewer);

        return $query
            ->limit(max($limit, 500))
            ->get()
            ->filter(fn (Idea $idea): bool => $viewer->can('view', $idea))
            ->take($limit)
            ->values();
    }

    public function search(User $viewer, string $search, int $limit = 8): Collection
    {
        $needle = $this->normalize($search);

        if (mb_strlen($needle) < 2) {
            return collect();
        }

        $query = Idea::query()
            ->with(['user', 'category'])
            ->orderByRaw('CASE WHEN ideas.user_id = ? THEN 0 ELSE 1 END', [$viewer->id])
            ->latest('ideas.updated_at');

        $this->applyAccessibleCandidateScope($query, $viewer);
        $this->applyNormalizedSearch($query, $search);

        // The SQL scope is deliberately broad enough to remain portable between
        // SQLite/MySQL. The policy is the final authority, including ancestor
        // restrictions in mixed-visibility idea trees.
        return $query
            ->limit(max(100, $limit * 20))
            ->get()
            ->filter(fn (Idea $idea): bool => $viewer->can('view', $idea))
            ->take($limit)
            ->values();
    }

    public function applyNormalizedSearch(Builder $query, string $search): Builder
    {
        $needle = $this->normalize($search);

        if ($needle === '') {
            return $query;
        }

        return $query->where(function (Builder $matches) use ($needle): void {
            foreach (['ideas.title', 'ideas.summary', 'ideas.description', 'ideas.problem_opportunity'] as $index => $column) {
                $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                $matches->{$method}($this->normalizedSql($column).' LIKE ?', ["%{$needle}%"]);
            }

            $matches
                ->orWhereHas('category', fn (Builder $category) => $category->whereRaw(
                    $this->normalizedSql('categories.name').' LIKE ?',
                    ["%{$needle}%"]
                ))
                ->orWhereHas('tags', fn (Builder $tags) => $tags->whereRaw(
                    $this->normalizedSql('tags.name').' LIKE ?',
                    ["%{$needle}%"]
                ))
                ->orWhereHas('user', function (Builder $author) use ($needle): void {
                    foreach (['users.name', 'users.job_title', 'users.department'] as $index => $column) {
                        $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                        $author->{$method}($this->normalizedSql($column).' LIKE ?', ["%{$needle}%"]);
                    }
                });
        });
    }

    public function normalize(string $value): string
    {
        return (string) Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '');
    }

    private function applyAccessibleCandidateScope(Builder $query, User $viewer): void
    {
        if ($viewer->isAdmin()) {
            return;
        }

        $viewerUnit = $viewer->effectiveOrganizationalUnit();
        $viewerPathIds = $viewerUnit?->ancestorAndSelfIds() ?? collect();

        $query->where(function (Builder $visible) use ($viewer, $viewerUnit, $viewerPathIds): void {
            $visible
                ->where('ideas.user_id', $viewer->id)
                ->orWhere('ideas.publication_status', 'published')
                ->orWhere(function (Builder $profile): void {
                    $profile
                        ->where('ideas.access_scope', 'profile')
                        ->where('ideas.visibility', '!=', 'draft');
                });

            if ($viewerUnit) {
                $visible->orWhere(function (Builder $internal) use ($viewerUnit, $viewerPathIds): void {
                    $internal
                        ->where('ideas.access_scope', 'organization')
                        ->where('ideas.visibility', '!=', 'draft')
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

    private function normalizedSql(string $column): string
    {
        $expression = "LOWER(COALESCE({$column}, ''))";
        $replacements = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
            ' ' => '',
            '-' => '',
            '_' => '',
            '.' => '',
            ',' => '',
            '/' => '',
        ];

        foreach ($replacements as $from => $to) {
            $expression = "REPLACE({$expression}, '{$from}', '{$to}')";
        }

        return $expression;
    }
}
