<?php

namespace App\AI\Services;

use App\Models\CategoryDimension;
use App\Models\Idea;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Services\GlobalIdeaSearchService;
use App\Services\IdeaHierarchyService;
use Illuminate\Support\Str;

class IdeaAiContextBuilder
{
    public function __construct(
        private readonly IdeaHierarchyService $hierarchy,
        private readonly GlobalIdeaSearchService $ideaSearch,
    ) {}

    public function build(User $user, ?Idea $currentIdea = null): array
    {
        $invalidParentIds = $currentIdea
            ? $this->hierarchy->descendantIds($currentIdea)->push($currentIdea->id)
            : collect();
        $dimensions = CategoryDimension::query()
            ->active()
            ->ordered()
            ->with(['categories' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->get()
            ->map(fn (CategoryDimension $dimension) => [
                'id' => $dimension->id,
                'name' => $dimension->name,
                'description' => $dimension->description,
                'is_primary' => $dimension->is_primary,
                'is_required' => $dimension->is_required,
                'selection_mode' => $dimension->selection_mode,
                'categories' => $dimension->categories->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'path' => $category->path_label,
                ])->values()->all(),
            ])->values();

        $ownIdeas = Idea::query()
            ->where('user_id', $user->id)
            ->when($currentIdea, fn ($query) => $query->whereKeyNot($currentIdea->id))
            ->whereNotIn('workspace_status', ['archivada', 'descartada'])
            ->whereNotIn('status', ['archivada', 'descartada'])
            ->with(['category:id,name', 'tags:id,name'])
            ->latest('updated_at')
            ->limit((int) config('ai.limits.context_ideas', 60))
            ->get()
            ->map(fn (Idea $idea) => [
                'id' => $idea->id,
                'title' => $idea->title,
                'description_excerpt' => Str::limit(strip_tags($idea->description), 420),
                'problem_opportunity' => Str::limit(strip_tags((string) $idea->problem_opportunity), 260),
                'primary_category_id' => $idea->category_id,
                'parent_idea_id' => $idea->parent_idea_id,
                'tags' => $idea->tags->pluck('name')->values()->all(),
                'author_id' => $idea->user_id,
                'author_name' => $user->name,
                'is_own' => true,
            ])->values();

        $accessibleIdeas = $this->ideaSearch
            ->accessibleCandidates($user, $currentIdea?->id ?? 0, (int) config('ai.limits.context_ideas', 60))
            ->load('tags')
            ->reject(fn (Idea $idea) => in_array($idea->workspace_status, ['archivada', 'descartada'], true)
                || in_array($idea->status, ['archivada', 'descartada'], true));
        $ideas = $accessibleIdeas->map(fn (Idea $idea) => [
            'id' => $idea->id,
            'title' => $idea->title,
            'description_excerpt' => Str::limit(strip_tags($idea->description), 420),
            'problem_opportunity' => Str::limit(strip_tags((string) $idea->problem_opportunity), 260),
            'primary_category_id' => $idea->category_id,
            'parent_idea_id' => $idea->parent_idea_id,
            'tags' => $idea->tags->pluck('name')->values()->all(),
            'author_id' => $idea->user_id,
            'author_name' => $idea->user?->name,
            'is_own' => $idea->user_id === $user->id,
        ])->values();

        $taskIdeaIds = $accessibleIdeas
            ->filter(fn (Idea $idea) => $idea->user_id === $user->id || $idea->allow_task_collaboration)
            ->reject(fn (Idea $idea) => in_array($idea->workspace_status, Idea::TERMINAL_WORKSPACE_STATUSES, true))
            ->pluck('id')
            ->values();

        $tasks = Task::query()
            ->active()
            ->with(['idea:id,title', 'creator:id,name'])
            ->where(function ($query) use ($user): void {
                $query->where('created_by_user_id', $user->id)
                    ->orWhere('assigned_to_user_id', $user->id)
                    ->orWhere(function ($open): void {
                        $open->where('participation_mode', 'open')
                            ->whereHas('idea', fn ($ideas) => $ideas->where('allow_task_collaboration', true));
                    });
            })
            ->latest('updated_at')
            ->limit(100)
            ->get()
            ->filter(fn (Task $task) => $user->can('view', $task))
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'description_excerpt' => Str::limit(strip_tags((string) $task->description), 300),
                'idea_id' => $task->idea_id,
                'idea_title' => $task->idea?->title,
                'parent_task_id' => $task->parent_task_id,
                'creator_name' => $task->creator?->name,
            ])->values();

        $tags = Tag::query()
            ->withCount(['ideas as own_ideas_count' => fn ($query) => $query->where('ideas.user_id', $user->id)])
            ->withCount('ideas')
            ->orderByDesc('own_ideas_count')
            ->orderByDesc('ideas_count')
            ->orderBy('name')
            ->limit((int) config('ai.limits.context_tags', 80))
            ->get()
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'usage_count' => $tag->ideas_count,
            ])->values();

        return [
            'taxonomy' => $dimensions->all(),
            'tag_candidates' => $tags->all(),
            'idea_candidates' => $ideas->all(),
            'parent_idea_candidates' => $ownIdeas->all(),
            'task_idea_candidates' => $ideas->whereIn('id', $taskIdeaIds)->values()->all(),
            'task_candidates' => $tasks->all(),
            'allowed_category_ids' => $dimensions->flatMap(fn ($dimension) => collect($dimension['categories'])->pluck('id'))->values()->all(),
            'allowed_dimension_ids' => $dimensions->pluck('id')->values()->all(),
            'allowed_tag_ids' => $tags->pluck('id')->values()->all(),
            'allowed_idea_ids' => $ideas->pluck('id')->values()->all(),
            'allowed_parent_idea_ids' => $ownIdeas->pluck('id')->diff($invalidParentIds)->values()->all(),
            'allowed_task_idea_ids' => $taskIdeaIds->all(),
            'allowed_task_ids' => $tasks->pluck('id')->values()->all(),
        ];
    }
}
