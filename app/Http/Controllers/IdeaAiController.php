<?php

namespace App\Http\Controllers;

use App\AI\Exceptions\AiUnavailableException;
use App\AI\Services\IdeaAiService;
use App\Http\Requests\AI\AnalyzeIdeaDraftRequest;
use App\Http\Requests\AI\SuggestIdeaRelationsRequest;
use App\Http\Requests\AI\TranscribeIdeaAudioRequest;
use App\Models\Idea;
use Illuminate\Http\JsonResponse;

class IdeaAiController extends Controller
{
    public function transcribe(TranscribeIdeaAudioRequest $request, IdeaAiService $service): JsonResponse
    {
        return $this->respond(fn () => $service->transcribe($request->user(), $request->file('audio')));
    }

    public function organize(AnalyzeIdeaDraftRequest $request, IdeaAiService $service): JsonResponse
    {
        $currentIdea = $this->currentIdea($request->user()->id, $request->integer('current_idea_id'));

        return $this->respond(fn () => $service->organize(
            $request->user(),
            $request->safe()->only(['transcript', 'title', 'description', 'problem_opportunity']),
            $currentIdea,
        ));
    }

    public function relations(SuggestIdeaRelationsRequest $request, IdeaAiService $service): JsonResponse
    {
        $currentIdea = $this->currentIdea($request->user()->id, $request->integer('current_idea_id'));

        return $this->respond(fn () => $service->relations(
            $request->user(),
            $request->safe()->only(['title', 'description', 'problem_opportunity']),
            $request->filled('parent_idea_id') ? $request->integer('parent_idea_id') : null,
            $currentIdea,
        ));
    }

    private function currentIdea(int $userId, int $ideaId): ?Idea
    {
        if ($ideaId < 1) {
            return null;
        }

        return Idea::query()->where('user_id', $userId)->find($ideaId);
    }

    private function respond(callable $callback): JsonResponse
    {
        try {
            return response()->json(['data' => $callback()]);
        } catch (AiUnavailableException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => $exception->errorCode,
            ], 503);
        }
    }
}
