<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RankingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Idea::with(['user', 'category'])
            ->communityPublished();

        // Timeframe filter
        $periodo = $request->input('periodo', 'historico');
        match ($periodo) {
            'semana' => $query->where('created_at', '>=', now()->subDays(7)),
            'mes' => $query->where('created_at', '>=', now()->subDays(30)),
            'anio' => $query->where('created_at', '>=', now()->startOfYear()),
            default => null, // 'historico'
        };

        // Category filter
        if ($categorySlug = $request->input('categoria')) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        // Department filter
        if ($department = $request->input('departamento')) {
            $query->whereHas('user', function ($q) use ($department) {
                $q->where('department', $department);
            });
        }

        // Status filter
        if ($status = $request->input('estado')) {
            $query->where('status', $status);
        }

        // Order by Innovation Score desc, then votes_count desc, then average_rating desc
        $allRanked = $query->orderByDesc('innovation_score')
            ->orderByDesc('votes_count')
            ->orderByDesc('average_rating')
            ->get();

        $top3 = $allRanked->take(3);
        $remaining = $allRanked->slice(3);

        $categories = Category::all();
        $departments = User::whereNotNull('department')->distinct()->pluck('department');

        return view('ranking.index', compact(
            'top3',
            'remaining',
            'categories',
            'departments',
            'periodo'
        ));
    }
}
