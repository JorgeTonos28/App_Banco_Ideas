<?php

namespace App\Http\Controllers;

use App\Http\Requests\Idea\UpdateIdeaHierarchyRequest;
use App\Models\Idea;
use App\Services\IdeaHierarchyService;
use Illuminate\Http\RedirectResponse;

class IdeaHierarchyController extends Controller
{
    public function update(UpdateIdeaHierarchyRequest $request, Idea $idea, IdeaHierarchyService $service): RedirectResponse
    {
        $parent = $request->filled('parent_idea_id')
            ? Idea::findOrFail($request->integer('parent_idea_id'))
            : null;

        $service->move($idea, $parent, $request->user(), $request->input('note'));

        return back()->with('success', 'La ubicación de la idea fue actualizada.');
    }
}
