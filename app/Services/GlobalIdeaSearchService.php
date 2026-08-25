<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GlobalIdeaSearchService
{
    public function search(User $viewer, string $search, int $limit = 8): Collection
    {
        $needle = $this->normalize($search);

        if (mb_strlen($needle) < 2) {
            return collect();
        }

        return Idea::query()
            ->with(['user', 'category'])
            ->where(function (Builder $visible) use ($viewer): void {
                $visible
                    ->where('user_id', $viewer->id)
                    ->orWhere('publication_status', 'published');
            })
            ->where(function (Builder $matches) use ($needle): void {
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
                    ));
            })
            ->orderByRaw('CASE WHEN ideas.user_id = ? THEN 0 ELSE 1 END', [$viewer->id])
            ->latest('ideas.updated_at')
            ->limit($limit)
            ->get();
    }

    public function normalize(string $value): string
    {
        return (string) Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '');
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
