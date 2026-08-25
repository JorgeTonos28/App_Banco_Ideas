<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Idea\ReviewIdeaPublicationRequest;
use App\Models\Idea;
use App\Services\IdeaPublicationService;
use Illuminate\Http\RedirectResponse;

class AdminIdeaPublicationController extends Controller
{
    public function update(ReviewIdeaPublicationRequest $request, Idea $idea, IdeaPublicationService $service): RedirectResponse
    {
        $service->review(
            $idea,
            $request->user(),
            $request->string('publication_status')->toString(),
            $request->string('community_display', 'hidden')->toString(),
            $request->input('publication_notes'),
        );

        return back()->with('success', 'La decisión editorial fue registrada correctamente.');
    }
}
