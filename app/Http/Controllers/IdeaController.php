<?php

namespace App\Http\Controllers;

use App\Http\Requests\Idea\StoreIdeaRequest;
use App\Http\Requests\Idea\UpdateIdeaRequest;
use App\Models\Category;
use App\Models\Idea;
use App\Models\IdeaAttachment;
use App\Models\IdeaFavorite;
use App\Models\IdeaRating;
use App\Models\IdeaStatusHistory;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IdeaController extends Controller
{
    /**
     * Display a listing of the resource (Explorar Ideas).
     */
    public function index(Request $request): View
    {
        $query = Idea::with(['user', 'category', 'tags'])
            ->where('visibility', 'public');

        // Search
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('problem_opportunity', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Category
        if ($categorySlug = $request->input('categoria')) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        // Filter by Tag
        if ($tagSlug = $request->input('etiqueta')) {
            $query->whereHas('tags', function ($q) use ($tagSlug) {
                $q->where('slug', $tagSlug);
            });
        }

        // Filter by Status
        if ($status = $request->input('estado')) {
            $query->where('status', $status);
        }

        // Filter by Author
        if ($authorId = $request->input('autor')) {
            $query->where('user_id', $authorId);
        }

        // Filter by Department
        if ($department = $request->input('departamento')) {
            $query->whereHas('user', function ($q) use ($department) {
                $q->where('department', $department);
            });
        }

        // Sorting
        $sort = $request->input('orden', 'recientes');
        match ($sort) {
            'mas_votadas' => $query->orderByDesc('votes_count')->orderByDesc('average_rating'),
            'tendencia' => $query->orderByDesc('innovation_score')->orderByDesc('votes_count'),
            'mas_comentadas' => $query->withCount('comments')->orderByDesc('comments_count'),
            'implementadas' => $query->where('status', 'implementada')->orderByDesc('implemented_at')->orderByDesc('created_at'),
            'mejor_valoradas' => $query->orderByDesc('average_rating')->orderByDesc('votes_count'),
            default => $query->latest(), // 'recientes'
        };

        $ideas = $query->paginate(9)->withQueryString();

        $categories = Category::withCount(['ideas' => function ($q) {
            $q->where('visibility', 'public');
        }])->get();

        $tags = Tag::withCount('ideas')->orderByDesc('ideas_count')->take(15)->get();

        $departments = User::whereNotNull('department')
            ->distinct()
            ->pluck('department');

        return view('ideas.index', compact('ideas', 'categories', 'tags', 'departments', 'sort'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        
        $allTags = Tag::withCount('ideas')
            ->with(['ideas' => function ($q) {
                $q->select('ideas.id', 'ideas.category_id');
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'ideas_count' => (int) $tag->ideas_count,
                    'category_ids' => $tag->ideas->pluck('category_id')->filter()->unique()->values()->all(),
                ];
            });

        $popularTags = Tag::withCount('ideas')->orderByDesc('ideas_count')->take(8)->get();
        $tags = $popularTags;

        return view('ideas.create', compact('categories', 'allTags', 'popularTags', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIdeaRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $idea = Idea::create([
                'user_id' => auth()->id(),
                'category_id' => $request->category_id,
                'title' => $request->title,
                'summary' => Str::limit(strip_tags($request->description), 160),
                'description' => $request->description,
                'problem_opportunity' => $request->problem_opportunity,
                'status' => 'nueva',
                'visibility' => $request->visibility,
            ]);

            // Handle Tags (create if not exist, support comma-separated items)
            if ($request->filled('tags')) {
                $tagIds = [];
                foreach ($request->tags as $tagItem) {
                    $exploded = explode(',', (string)$tagItem);
                    foreach ($exploded as $rawName) {
                        $tagName = trim(ltrim(trim($rawName), '#'));
                        if ($tagName !== '') {
                            $tag = Tag::firstOrCreate(
                                ['name' => $tagName],
                                ['slug' => Str::slug($tagName)]
                            );
                            $tagIds[] = $tag->id;
                        }
                    }
                }
                $idea->tags()->sync(array_values(array_unique($tagIds)));
            }

            // Handle Attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('idea_attachments/' . $idea->id, 'public');
                    $idea->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            // Record Initial Status History
            IdeaStatusHistory::create([
                'idea_id' => $idea->id,
                'user_id' => auth()->id(),
                'old_status' => null,
                'new_status' => 'nueva',
                'comment' => $idea->visibility === 'public'
                    ? 'Idea publicada en el banco institucional INNOVATEP.'
                    : 'Borrador inicial guardado.',
            ]);

            $idea->recalculateRatingAndScore();

            DB::commit();

            $message = $idea->visibility === 'public'
                ? '¡Tu idea ha sido publicada con éxito y ya forma parte del Banco INNOVATEP!'
                : 'Borrador guardado correctamente. Puedes completarlo cuando estés listo.';

            return redirect()->route('ideas.show', $idea->slug)->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Ocurrió un error al registrar la idea: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug): View
    {
        $idea = Idea::with([
            'user',
            'category',
            'tags',
            'attachments',
            'statusHistories.user',
            'comments' => function ($query) {
                $query->whereNull('parent_id')
                      ->with(['user', 'likes', 'replies.user', 'replies.likes'])
                      ->latest();
            },
        ])->where('slug', $slug)->firstOrFail();

        // Check view authorization for drafts
        if ($idea->visibility === 'draft' && (!auth()->check() || (!auth()->user()->isAdmin() && auth()->id() !== $idea->user_id))) {
            abort(403, 'Esta idea se encuentra en borrador y no es pública.');
        }

        // Increment views count safely
        $sessionKey = 'viewed_idea_' . $idea->id;
        if (!session()->has($sessionKey)) {
            $idea->increment('views_count');
            session()->put($sessionKey, true);
            $idea->recalculateRatingAndScore();
        }

        // Related ideas in the same category
        $relatedIdeas = Idea::with(['user', 'category'])
            ->where('category_id', $idea->category_id)
            ->where('id', '!=', $idea->id)
            ->where('visibility', 'public')
            ->orderByDesc('innovation_score')
            ->take(3)
            ->get();

        return view('ideas.show', compact('idea', 'relatedIdeas'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Idea $idea): View
    {
        $this->authorize('update', $idea);

        $categories = Category::orderBy('name')->get();
        
        $allTags = Tag::withCount('ideas')
            ->with(['ideas' => function ($q) {
                $q->select('ideas.id', 'ideas.category_id');
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'ideas_count' => (int) $tag->ideas_count,
                    'category_ids' => $tag->ideas->pluck('category_id')->filter()->unique()->values()->all(),
                ];
            });

        $popularTags = Tag::withCount('ideas')->orderByDesc('ideas_count')->take(8)->get();
        $tags = $popularTags;
        $selectedTags = $idea->tags->pluck('name')->toArray();

        return view('ideas.edit', compact('idea', 'categories', 'allTags', 'popularTags', 'tags', 'selectedTags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIdeaRequest $request, Idea $idea): RedirectResponse
    {
        $this->authorize('update', $idea);

        DB::beginTransaction();
        try {
            $idea->update([
                'title' => $request->title,
                'summary' => Str::limit(strip_tags($request->description), 160),
                'description' => $request->description,
                'problem_opportunity' => $request->problem_opportunity,
                'category_id' => $request->category_id,
                'visibility' => $request->visibility,
            ]);

            // Sync Tags (create if not exist, support comma-separated items)
            if ($request->filled('tags')) {
                $tagIds = [];
                foreach ($request->tags as $tagItem) {
                    $exploded = explode(',', (string)$tagItem);
                    foreach ($exploded as $rawName) {
                        $tagName = trim(ltrim(trim($rawName), '#'));
                        if ($tagName !== '') {
                            $tag = Tag::firstOrCreate(
                                ['name' => $tagName],
                                ['slug' => Str::slug($tagName)]
                            );
                            $tagIds[] = $tag->id;
                        }
                    }
                }
                $idea->tags()->sync(array_values(array_unique($tagIds)));
            } else {
                $idea->tags()->detach();
            }

            // Delete requested attachments
            if ($request->filled('delete_attachments')) {
                $attachmentsToDelete = IdeaAttachment::whereIn('id', $request->delete_attachments)
                    ->where('idea_id', $idea->id)
                    ->get();

                foreach ($attachmentsToDelete as $att) {
                    Storage::disk('public')->delete($att->file_path);
                    $att->delete();
                }
            }

            // Handle New Attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('idea_attachments/' . $idea->id, 'public');
                    $idea->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            $idea->recalculateRatingAndScore();

            DB::commit();

            return redirect()->route('ideas.show', $idea->slug)->with('success', '¡Idea actualizada con éxito!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al actualizar la idea: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Idea $idea): RedirectResponse
    {
        $this->authorize('delete', $idea);

        // Delete attachment files from storage
        foreach ($idea->attachments as $att) {
            Storage::disk('public')->delete($att->file_path);
        }

        $idea->delete();

        return redirect()->route('ideas.index')->with('success', 'La idea ha sido eliminada.');
    }

    /**
     * Rate / Vote on an idea (1 to 5 stars).
     */
    public function vote(Request $request, Idea $idea): JsonResponse|RedirectResponse
    {
        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Debes iniciar sesión para votar.'], 401);
            }
            return redirect()->route('login');
        }

        if ($idea->user_id === auth()->id()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No puedes votar por tu propia idea.'], 422);
            }
            return back()->with('error', 'No puedes votar por tu propia idea.');
        }

        if ($idea->visibility !== 'public' || in_array($idea->status, ['descartada', 'archivada'])) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Esta idea no admite votaciones.'], 422);
            }
            return back()->with('error', 'Esta idea no admite votaciones en su estado actual.');
        }

        // Upsert rating
        IdeaRating::updateOrCreate(
            ['idea_id' => $idea->id, 'user_id' => auth()->id()],
            ['rating' => $request->integer('rating')]
        );

        $idea->recalculateRatingAndScore();
        $idea->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => '¡Valoración registrada con éxito!',
                'user_rating' => $request->integer('rating'),
                'average_rating' => number_format($idea->average_rating, 1),
                'votes_count' => $idea->votes_count,
                'innovation_score' => $idea->innovation_score,
            ]);
        }

        return back()->with('success', '¡Tu valoración de ' . $request->rating . ' estrellas ha sido registrada!');
    }

    /**
     * Toggle Favorite / Bookmark.
     */
    public function toggleFavorite(Idea $idea): JsonResponse|RedirectResponse
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $fav = IdeaFavorite::where('idea_id', $idea->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($fav) {
            $fav->delete();
            $isFavorited = false;
            $msg = 'Idea removida de tus guardadas.';
        } else {
            IdeaFavorite::create(['idea_id' => $idea->id, 'user_id' => auth()->id()]);
            $isFavorited = true;
            $msg = 'Idea guardada en tus favoritas.';
        }

        if (request()->expectsJson()) {
            return response()->json(['is_favorited' => $isFavorited, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}
