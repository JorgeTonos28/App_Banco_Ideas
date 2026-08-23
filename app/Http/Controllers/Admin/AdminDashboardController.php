<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Idea;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        // 1. Core KPIs
        $totalIdeas = Idea::count();
        $newThisMonth = Idea::where('created_at', '>=', now()->startOfMonth())->count();
        $inReview = Idea::where('status', 'en_revision')->count();
        $prioritized = Idea::where('status', 'priorizada')->count();
        $inDevelopment = Idea::where('status', 'en_desarrollo')->count();
        $implemented = Idea::where('status', 'implementada')->count();
        $discarded = Idea::where('status', 'descartada')->count();
        $activeUsers = User::where('is_active', true)->count();

        // 2. Ideas requiring urgent attention (Nueva or En revisión without assigned reviewer or pending review)
        $pendingIdeas = Idea::with(['user', 'category'])
            ->whereIn('status', ['nueva', 'en_revision'])
            ->orderBy('created_at', 'asc')
            ->take(6)
            ->get();

        // 3. Ideas by Status breakdown
        $statusCounts = [
            'nueva' => Idea::where('status', 'nueva')->count(),
            'en_revision' => $inReview,
            'priorizada' => $prioritized,
            'en_desarrollo' => $inDevelopment,
            'implementada' => $implemented,
            'descartada' => $discarded,
            'archivada' => Idea::where('status', 'archivada')->count(),
        ];

        // 4. Ideas by Category breakdown
        $categoriesBreakdown = Category::withCount('ideas')->orderByDesc('ideas_count')->get();

        // 5. Active departments ranking
        $departmentsRanking = User::query()
            ->select('users.department')
            ->selectRaw('COUNT(ideas.id) AS ideas_count')
            ->leftJoin('ideas', 'ideas.user_id', '=', 'users.id')
            ->whereNotNull('users.department')
            ->groupBy('users.department')
            ->orderByDesc('ideas_count')
            ->take(5)
            ->get();

        // 6. Top scored ideas
        $topScoredIdeas = Idea::with(['user', 'category'])
            ->orderByDesc('innovation_score')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalIdeas',
            'newThisMonth',
            'inReview',
            'prioritized',
            'inDevelopment',
            'implemented',
            'discarded',
            'activeUsers',
            'pendingIdeas',
            'statusCounts',
            'categoriesBreakdown',
            'departmentsRanking',
            'topScoredIdeas'
        ));
    }
}
