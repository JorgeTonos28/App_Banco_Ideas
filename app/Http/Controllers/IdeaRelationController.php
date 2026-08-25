<?php

namespace App\Http\Controllers;

use App\Http\Requests\Idea\DeleteIdeaRelationRequest;
use App\Http\Requests\Idea\ReviewIdeaRelationRequest;
use App\Http\Requests\Idea\StoreIdeaRelationRequest;
use App\Http\Requests\Idea\UpdateIdeaRelationRequest;
use App\Models\Idea;
use App\Models\IdeaRelation;
use App\Services\IdeaRelationService;
use Illuminate\Http\RedirectResponse;

class IdeaRelationController extends Controller
{
    public function store(StoreIdeaRelationRequest $request, Idea $idea, IdeaRelationService $service): RedirectResponse
    {
        $target = Idea::findOrFail($request->integer('target_idea_id'));

        $relation = $service->create(
            $idea,
            $target,
            $request->user(),
            $request->string('type')->toString(),
            $request->input('rationale'),
        );

        $message = $relation->status === 'approved'
            ? 'La relación entre ideas fue registrada.'
            : 'La relación fue propuesta y espera confirmación del otro autor.';

        return back()->with('success', $message);
    }

    public function update(ReviewIdeaRelationRequest $request, IdeaRelation $ideaRelation, IdeaRelationService $service): RedirectResponse
    {
        $service->review($ideaRelation, $request->user(), $request->string('status')->toString());

        return back()->with('success', 'La relación fue revisada correctamente.');
    }

    public function updateDetails(
        UpdateIdeaRelationRequest $request,
        IdeaRelation $ideaRelation,
        IdeaRelationService $service
    ): RedirectResponse {
        $relation = $service->updateDetails(
            $ideaRelation,
            $request->user(),
            $request->string('type')->toString(),
            $request->input('rationale'),
        );

        $message = $relation->status === 'pending'
            ? 'La relación fue actualizada y requiere una nueva confirmación del otro autor.'
            : 'La relación fue actualizada correctamente.';

        return back()->with('success', $message);
    }

    public function destroy(DeleteIdeaRelationRequest $request, IdeaRelation $ideaRelation): RedirectResponse
    {
        $ideaRelation->delete();

        return back()->with('success', 'La relación fue eliminada.');
    }
}
