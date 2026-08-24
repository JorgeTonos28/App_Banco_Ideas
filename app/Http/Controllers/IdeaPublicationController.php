<?php

namespace App\Http\Controllers;

use App\Http\Requests\Idea\CancelIdeaPublicationRequest;
use App\Http\Requests\Idea\RequestIdeaPublicationRequest;
use App\Models\Idea;
use App\Services\IdeaPublicationService;
use Illuminate\Http\RedirectResponse;

class IdeaPublicationController extends Controller
{
    public function store(RequestIdeaPublicationRequest $request, Idea $idea, IdeaPublicationService $service): RedirectResponse
    {
        $service->requestPublication($idea, $request->user());

        return back()->with('success', 'La idea fue enviada a revisión editorial. Permanecerá privada hasta que sea aprobada.');
    }

    public function destroy(CancelIdeaPublicationRequest $request, Idea $idea, IdeaPublicationService $service): RedirectResponse
    {
        $service->cancelRequest($idea, $request->user());

        return back()->with('success', 'La solicitud de publicación fue cancelada.');
    }
}
