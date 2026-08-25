<?php

namespace App\Services;

use App\Models\Idea;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IdeaTreeService
{
    public function prepare(Collection $ideas): array
    {
        $ideas = $ideas->values();
        $ideaIds = $ideas->pluck('id')->all();
        $treeByParent = $ideas->whereNotNull('parent_idea_id')->groupBy('parent_idea_id');
        $roots = $ideas->filter(
            fn (Idea $idea) => ! $idea->parent_idea_id || ! in_array($idea->parent_idea_id, $ideaIds, true)
        )->values();

        $ownSearchTerms = $ideas->mapWithKeys(
            fn (Idea $idea) => [$idea->id => $this->searchableText($idea)]
        );
        $branchSearchTerms = collect();

        foreach ($ideas as $idea) {
            $this->branchSearchText($idea, $treeByParent, $ownSearchTerms, $branchSearchTerms, []);
        }

        return [
            'roots' => $roots,
            'byParent' => $treeByParent,
            'searchTerms' => $branchSearchTerms,
        ];
    }

    private function branchSearchText(
        Idea $idea,
        Collection $treeByParent,
        Collection $ownSearchTerms,
        Collection $branchSearchTerms,
        array $path
    ): string {
        if ($branchSearchTerms->has($idea->id)) {
            return $branchSearchTerms->get($idea->id);
        }

        if (isset($path[$idea->id])) {
            return $ownSearchTerms->get($idea->id, '');
        }

        $path[$idea->id] = true;
        $terms = $ownSearchTerms->get($idea->id, '');

        foreach ($treeByParent->get($idea->id, collect()) as $child) {
            $terms .= $this->branchSearchText(
                $child,
                $treeByParent,
                $ownSearchTerms,
                $branchSearchTerms,
                $path
            );
        }

        $branchSearchTerms->put($idea->id, $terms);

        return $terms;
    }

    private function searchableText(Idea $idea): string
    {
        $values = [
            $idea->title,
            $idea->summary,
            $idea->description,
            $idea->problem_opportunity,
            $idea->category?->name,
            $idea->category?->path_label,
            $idea->workspace_status_label,
            $idea->publication_status_label,
            ...$idea->tags->pluck('name')->all(),
        ];

        return $this->normalize(implode(' ', array_filter($values)));
    }

    private function normalize(string $value): string
    {
        return (string) Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '');
    }
}
