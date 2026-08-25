<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Idea;
use App\Models\IdeaRating;
use App\Models\Regional;
use App\Models\User;
use App\Services\IdeaCommunityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Resolve the application entry point for guests and authenticated users.
     */
    public function root(): RedirectResponse
    {
        return auth()->check()
            ? redirect()->route('my-ideas.index')
            : redirect()->route('login');
    }

    public function index(Request $request, IdeaCommunityService $communityService): View|RedirectResponse
    {
        $requestedLevel = $request->string('nivel')->toString();
        $user = $request->user();
        $userUnit = $user->effectiveOrganizationalUnit();

        if ($requestedLevel === '') {
            return $userUnit
                ? redirect()->route('community', ['nivel' => $userUnit->id])
                : redirect()->route('community', ['nivel' => 'general']);
        }

        if ($requestedLevel !== 'general') {
            return $this->organizationalCommunity($request, $communityService, $requestedLevel);
        }

        // 1. Overall Platform Stats
        $totalIdeas = Idea::communityPublished()->count();
        $thisMonthIdeas = Idea::communityPublished()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $implementedIdeas = Idea::communityPublished()->where('status', 'implementada')->count();
        $totalParticipants = User::where('is_active', true)->count();
        $totalVotes = IdeaRating::count();

        // 2. Featured Ideas (up to 4)
        $featuredIdeas = Idea::with(['user', 'category', 'tags'])
            ->withCount(['children as published_children_count' => fn ($query) => $query->published()->where('community_display', 'represented_by_parent')])
            ->communityPublished()
            ->where(function ($query) {
                $query->where('is_featured', true)
                    ->orWhere('innovation_score', '>=', 75);
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('innovation_score')
            ->take(4)
            ->get();

        // 3. Trending Ideas (receiving recent activity/votes)
        $trendingIdeas = Idea::with(['user', 'category'])
            ->withCount(['children as published_children_count' => fn ($query) => $query->published()->where('community_display', 'represented_by_parent')])
            ->communityPublished()
            ->orderByDesc('votes_count')
            ->orderByDesc('innovation_score')
            ->take(5)
            ->get();

        // 4. Latest Ideas Feed
        $latestIdeas = Idea::with(['user', 'category', 'tags'])
            ->withCount(['children as published_children_count' => fn ($query) => $query->published()->where('community_display', 'represented_by_parent')])
            ->communityPublished()
            ->latest()
            ->take(6)
            ->get();

        // 5. Popular Categories
        $popularCategories = Category::withCount(['ideas' => function ($query) {
            $query->communityPublished();
        }])
            ->orderByDesc('ideas_count')
            ->take(6)
            ->get();

        // 6. Top Innovators
        $topInnovators = User::withCount(['ideas' => function ($query) {
            $query->communityPublished();
        }])
            ->where('is_active', true)
            ->orderByDesc('ideas_count')
            ->take(5)
            ->get();

        $communityMode = 'general';
        $currentCommunity = null;
        $communityPath = collect();
        $upUnit = null;
        $downUnits = $userUnit
            ? collect([$userUnit->ancestors()->first() ?: $userUnit])
            : collect();

        return view('home', compact(
            'totalIdeas',
            'thisMonthIdeas',
            'implementedIdeas',
            'totalParticipants',
            'totalVotes',
            'featuredIdeas',
            'trendingIdeas',
            'latestIdeas',
            'popularCategories',
            'topInnovators',
            'communityMode',
            'currentCommunity',
            'communityPath',
            'upUnit',
            'downUnits'
        ));
    }

    private function organizationalCommunity(
        Request $request,
        IdeaCommunityService $communityService,
        string $requestedLevel
    ): View {
        abort_unless(ctype_digit($requestedLevel), 404);

        $unit = Regional::query()
            ->where('is_active', true)
            ->with(['parent', 'children' => fn ($children) => $children->where('is_active', true)])
            ->findOrFail((int) $requestedLevel);

        abort_unless($communityService->canNavigateTo($request->user(), $unit), 403);

        $baseQuery = $communityService->ideasForUnit($unit, $request->user());

        $totalIdeas = (clone $baseQuery)->count();
        $thisMonthIdeas = (clone $baseQuery)->where('created_at', '>=', now()->startOfMonth())->count();
        $completedIdeas = (clone $baseQuery)->where(function ($statuses): void {
            $statuses
                ->where(fn ($published) => $published->published()->where('status', 'implementada'))
                ->orWhere(fn ($internal) => $internal->where('publication_status', '!=', 'published')->where('workspace_status', 'completada'));
        })->count();
        $totalVotes = (clone $baseQuery)->sum('votes_count');

        $participantUnitIds = $unit->descendantIds(includeSelf: true);
        $totalParticipants = User::query()
            ->where('is_active', true)
            ->whereIn('organizational_unit_id', $participantUnitIds)
            ->count();

        $ideas = $baseQuery
            ->with(['user.organizationalUnit', 'category', 'tags'])
            ->withCount(['comments', 'children as visible_children_count'])
            ->latest()
            ->paginate(9)
            ->withQueryString();

        foreach ($ideas as $idea) {
            $idea->setAttribute('published_children_count', $idea->visible_children_count);
        }

        $communityMode = 'organizational';
        $currentCommunity = $unit;
        $communityPath = $unit->path();
        $upUnit = $unit->parent;
        $downUnits = $this->downUnitsFor($request->user()->effectiveOrganizationalUnit(), $unit);

        return view('community.local', compact(
            'ideas',
            'totalIdeas',
            'thisMonthIdeas',
            'completedIdeas',
            'totalParticipants',
            'totalVotes',
            'communityMode',
            'currentCommunity',
            'communityPath',
            'upUnit',
            'downUnits'
        ));
    }

    private function downUnitsFor(?Regional $userUnit, Regional $currentUnit): Collection
    {
        if (! $userUnit) {
            return collect();
        }

        if ($userUnit->isDescendantOf($currentUnit)) {
            $path = $userUnit->path()->values();
            $currentIndex = $path->search(fn (Regional $pathUnit) => $pathUnit->is($currentUnit));

            return $currentIndex !== false && $path->has($currentIndex + 1)
                ? collect([$path->get($currentIndex + 1)])
                : collect();
        }

        if ($userUnit->is($currentUnit)) {
            return $currentUnit->children()->where('is_active', true)->get();
        }

        return collect();
    }
}
