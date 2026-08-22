<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Idea;
use App\Models\IdeaRating;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function index(Request $request): View
    {
        // 1. Overall Platform Stats
        $totalIdeas = Idea::where('visibility', 'public')->count();
        $thisMonthIdeas = Idea::where('visibility', 'public')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $implementedIdeas = Idea::where('status', 'implementada')->count();
        $totalParticipants = User::where('is_active', true)->count();
        $totalVotes = IdeaRating::count();

        // 2. Featured Ideas (up to 4)
        $featuredIdeas = Idea::with(['user', 'category', 'tags'])
            ->where('visibility', 'public')
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
            ->where('visibility', 'public')
            ->orderByDesc('votes_count')
            ->orderByDesc('innovation_score')
            ->take(5)
            ->get();

        // 4. Latest Ideas Feed
        $latestIdeas = Idea::with(['user', 'category', 'tags'])
            ->where('visibility', 'public')
            ->latest()
            ->take(6)
            ->get();

        // 5. Popular Categories
        $popularCategories = Category::withCount(['ideas' => function ($query) {
            $query->where('visibility', 'public');
        }])
        ->orderByDesc('ideas_count')
        ->take(6)
        ->get();

        // 6. Top Innovators
        $topInnovators = User::withCount(['ideas' => function ($query) {
            $query->where('visibility', 'public');
        }])
        ->where('is_active', true)
        ->orderByDesc('ideas_count')
        ->take(5)
        ->get();

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
            'topInnovators'
        ));
    }
}
