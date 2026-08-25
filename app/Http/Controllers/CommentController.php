<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\StoreCommentRequest;
use App\Models\Idea;
use App\Models\IdeaComment;
use App\Models\IdeaCommentLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Idea $idea): RedirectResponse
    {
        $comment = $idea->comments()->create([
            'user_id' => auth()->id(),
            'parent_id' => $request->parent_id,
            'content' => $request->content,
        ]);

        $idea->recalculateRatingAndScore();

        return back()->with('success', 'Tu comentario ha sido publicado.');
    }

    public function toggleLike(IdeaComment $comment): JsonResponse|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $this->authorize('view', $comment->idea);
        abort_unless($comment->idea->isPublished(), 403);

        $existing = IdeaCommentLike::where('idea_comment_id', $comment->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existing) {
            $existing->delete();
            $comment->decrement('likes_count');
            $liked = false;
        } else {
            IdeaCommentLike::create([
                'idea_comment_id' => $comment->id,
                'user_id' => auth()->id(),
            ]);
            $comment->increment('likes_count');
            $liked = true;
        }

        if (request()->expectsJson()) {
            return response()->json([
                'liked' => $liked,
                'likes_count' => $comment->likes_count,
            ]);
        }

        return back();
    }

    public function destroy(IdeaComment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $idea = $comment->idea;
        $comment->delete();
        $idea->recalculateRatingAndScore();

        return back()->with('success', 'El comentario ha sido eliminado.');
    }
}
