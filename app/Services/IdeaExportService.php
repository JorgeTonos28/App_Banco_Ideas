<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Support\Collection;

class IdeaExportService
{
    public const OPTIONAL_FIELDS = ['description', 'problem_opportunity', 'tags', 'categories', 'relations'];

    public function build(Idea $root, User $viewer, array $fields): array
    {
        $ideas = collect([$root->loadMissing(['category', 'categories.dimension', 'tags', 'user'])]);
        $pending = collect([$root->id]);
        $visited = [$root->id => true];

        while ($pending->isNotEmpty() && $ideas->count() < 500) {
            $level = Idea::query()
                ->whereIn('parent_idea_id', $pending)
                ->with(['category', 'categories.dimension', 'tags', 'user'])
                ->orderBy('title')
                ->get()
                ->reject(fn (Idea $idea) => isset($visited[$idea->id]))
                ->filter(fn (Idea $idea) => $viewer->can('view', $idea));

            foreach ($level as $idea) {
                $visited[$idea->id] = true;
            }
            $ideas = $ideas->concat($level)->unique('id')->values();
            $pending = $level->pluck('id');
        }

        if (in_array('relations', $fields, true)) {
            $ideas->load(['outgoingRelations.targetIdea.user']);
        }

        $byParent = $ideas->whereNotNull('parent_idea_id')->groupBy('parent_idea_id');

        return [
            'exported_at' => now()->toIso8601String(),
            'root_id' => $root->id,
            'fields' => array_values($fields),
            'idea' => $this->node($root, $byParent, $viewer, $fields, []),
        ];
    }

    private function node(Idea $idea, Collection $byParent, User $viewer, array $fields, array $path): array
    {
        if (isset($path[$idea->id])) {
            return ['id' => $idea->id, 'title' => $idea->title, 'children' => []];
        }
        $path[$idea->id] = true;

        $data = ['id' => $idea->id, 'title' => $idea->title];
        if (in_array('description', $fields, true)) {
            $data['description'] = $idea->description;
        }
        if (in_array('problem_opportunity', $fields, true)) {
            $data['problem_opportunity'] = $idea->problem_opportunity;
        }
        if (in_array('tags', $fields, true)) {
            $data['tags'] = $idea->tags->pluck('name')->values()->all();
        }
        if (in_array('categories', $fields, true)) {
            $data['categories'] = $idea->categories->map(fn ($category) => [
                'dimension' => $category->dimension?->name,
                'name' => $category->name,
            ])->values()->all();
        }
        if (in_array('relations', $fields, true)) {
            $data['relations'] = $idea->outgoingRelations
                ->filter(fn ($relation) => ($relation->status === 'approved' || $viewer->can('update', $relation))
                    && $relation->targetIdea
                    && $viewer->can('view', $relation->targetIdea))
                ->map(fn ($relation) => [
                    'type' => $relation->type_label,
                    'target_title' => $relation->targetIdea->title,
                    'target_author' => $relation->targetIdea->user?->name,
                    'rationale' => $relation->rationale,
                    'status' => $relation->status_label,
                ])->values()->all();
        }

        $data['children'] = $byParent->get($idea->id, collect())
            ->map(fn (Idea $child) => $this->node($child, $byParent, $viewer, $fields, $path))
            ->values()->all();

        return $data;
    }
}
