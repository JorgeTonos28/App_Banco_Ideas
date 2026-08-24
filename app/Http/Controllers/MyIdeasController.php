<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MyIdeasController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Metrics
        $totalIdeas = $user->ideas()->count();
        $inReviewCount = $user->ideas()->where('publication_status', 'pending_review')->count();
        $prioritizedCount = $user->ideas()->published()->where('status', 'priorizada')->count();
        $inDevelopmentCount = $user->ideas()->where(function ($query) {
            $query->where(fn ($published) => $published->published()->where('status', 'en_desarrollo'))
                ->orWhere(fn ($private) => $private->where('visibility', 'private')->where('workspace_status', 'en_ejecucion'));
        })->count();
        $implementedCount = $user->ideas()->where(function ($query) {
            $query->where(fn ($published) => $published->published()->where('status', 'implementada'))
                ->orWhere(fn ($private) => $private->where('visibility', 'private')->where('workspace_status', 'completada'));
        })->count();

        $activeTab = $request->input('tab', 'privadas');

        $ideasQuery = match ($activeTab) {
            'borradores' => $user->ideas()->where('visibility', 'draft')->latest(),
            'implementadas' => $user->ideas()->where(function ($query) {
                $query->where(fn ($published) => $published->published()->where('status', 'implementada'))
                    ->orWhere(fn ($private) => $private->where('visibility', 'private')->where('workspace_status', 'completada'));
            })->latest(),
            'archivadas' => $user->ideas()->where(function ($query) {
                $query->where(fn ($published) => $published->published()->whereIn('status', ['archivada', 'descartada']))
                    ->orWhere(fn ($private) => $private->whereIn('workspace_status', ['archivada', 'descartada']));
            })->latest(),
            'guardadas' => $user->favoriteIdeas()->with(['user', 'category', 'tags'])->latest(),
            'publicadas' => $user->ideas()->published()->whereNotIn('status', ['archivada', 'descartada'])->latest(),
            default => $user->ideas()
                ->where('visibility', 'private')
                ->whereNotIn('workspace_status', ['archivada', 'descartada'])
                ->latest(),
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
