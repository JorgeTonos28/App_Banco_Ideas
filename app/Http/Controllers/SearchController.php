<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalSearchRequest;
use App\Models\Category;
use App\Models\Idea;
use App\Models\Tag;
use App\Models\User;
use App\Services\GlobalIdeaSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function globalSearch(GlobalSearchRequest $request, GlobalIdeaSearchService $ideaSearch): JsonResponse
    {
        $query = trim((string) ($request->validated('q') ?? ''));
        $normalizedQuery = $ideaSearch->normalize($query);

        if (mb_strlen($normalizedQuery) < 2) {
            return response()->json([
                'ideas' => [],
                'people' => [],
                'categories' => [],
                'tags' => [],
            ]);
        }

        $ideas = $ideaSearch->search($request->user(), $query)
            ->map(fn (Idea $idea) => [
                'id' => $idea->id,
                'title' => $idea->title,
                'summary' => $idea->summary ?: Str::limit(strip_tags($idea->description), 120),
                'url' => route('ideas.show', $idea->slug),
                'category' => $idea->category?->name,
                'status' => $idea->isPublished() ? $idea->status_label : $idea->workspace_status_label,
                'context' => match (true) {
                    $idea->user_id === $request->user()->id => 'Tu idea',
                    $idea->isPublished() => 'Comunidad general',
                    $idea->access_scope === 'organization' => 'Comunidad interna',
                    $idea->access_scope === 'profile' => 'Perfil visible',
                    default => 'Administración',
                },
            ]);

        $people = User::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('job_title', 'like', "%{$query}%")
                    ->orWhere('department', 'like', "%{$query}%");
            })
            ->take(5)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'job_title' => $u->job_title,
                'department' => $u->department,
                'avatar' => $u->avatar_url,
                'url' => route('profile.show', $u->id),
            ]);

        $categories = Category::where('name', 'like', "%{$query}%")
            ->take(4)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'icon' => $c->icon,
                'url' => route('ideas.index', ['categoria' => $c->slug]),
            ]);

        $tags = Tag::where('name', 'like', "%{$query}%")
            ->take(6)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'url' => route('ideas.index', ['etiqueta' => $t->slug]),
            ]);

        return response()->json([
            'ideas' => $ideas,
            'people' => $people,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }
}
