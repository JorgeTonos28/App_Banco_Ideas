<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Idea;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function globalSearch(Request $request): JsonResponse
    {
        $query = trim($request->input('q', ''));

        if (strlen($query) < 2) {
            return response()->json([
                'ideas' => [],
                'people' => [],
                'categories' => [],
                'tags' => [],
            ]);
        }

        $ideas = Idea::with(['user', 'category'])
            ->published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('summary', 'like', "%{$query}%");
            })
            ->take(5)
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'summary' => $i->summary,
                'url' => route('ideas.show', $i->slug),
                'category' => $i->category?->name,
                'status' => $i->status_label,
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
