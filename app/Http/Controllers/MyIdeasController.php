<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyIdeasController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Metrics
        $totalIdeas = $user->ideas()->count();
        $inReviewCount = $user->ideas()->where('status', 'en_revision')->count();
        $prioritizedCount = $user->ideas()->where('status', 'priorizada')->count();
        $inDevelopmentCount = $user->ideas()->where('status', 'en_desarrollo')->count();
        $implementedCount = $user->ideas()->where('status', 'implementada')->count();

        $activeTab = $request->input('tab', 'publicadas');

        $ideasQuery = match ($activeTab) {
            'borradores' => $user->ideas()->where('visibility', 'draft')->latest(),
            'implementadas' => $user->ideas()->where('status', 'implementada')->latest(),
            'archivadas' => $user->ideas()->whereIn('status', ['archivada', 'descartada'])->latest(),
            'guardadas' => $user->favoriteIdeas()->with(['user', 'category', 'tags'])->latest(),
            default => $user->ideas()->where('visibility', 'public')->whereNotIn('status', ['archivada', 'descartada'])->latest(), // 'publicadas'
        };

        $ideas = $ideasQuery->with(['category', 'tags'])->paginate(8)->withQueryString();

        return view('my_ideas.index', compact(
            'totalIdeas',
            'inReviewCount',
            'prioritizedCount',
            'inDevelopmentCount',
            'implementedCount',
            'activeTab',
            'ideas'
        ));
    }
}
