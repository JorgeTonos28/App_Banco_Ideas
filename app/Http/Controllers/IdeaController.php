<?php

namespace App\Http\Controllers;

use App\Http\Requests\Idea\StoreIdeaRequest;
use App\Http\Requests\Idea\UpdateIdeaRequest;
use App\Models\Category;
use App\Models\CategoryDimension;
use App\Models\Idea;
use App\Models\IdeaAttachment;
use App\Models\IdeaFavorite;
use App\Models\IdeaRating;
use App\Models\IdeaStatusHistory;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Services\GlobalIdeaSearchService;
use App\Services\IdeaClassificationService;
use App\Services\IdeaCommunityService;
use App\Services\IdeaHierarchyService;
use App\Services\IdeaRelationFormService;
use App\Services\IdeaStatusCascadeService;
use App\Services\IdeaTreeService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IdeaController extends Controller
{
    /**
     * Display a listing of the resource (Explorar Ideas).
     */
    public function index(Request $request, GlobalIdeaSearchService $ideaSearch): View
    {
        $query = Idea::with(['user', 'category', 'tags'])
            ->withCount([
                'children as published_children_count' => fn ($children) => $children->published()->where('community_display', 'represented_by_parent'),
            ])
            ->communityPublished();

        // Search
        if ($search = $request->string('q')->trim()->toString()) {
            $ideaSearch->applyNormalizedSearch($query, $search);
        }

        // Filter by Category
        if ($categorySlug = $request->input('categoria')) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        $facetSelections = collect($request->input('facetas', []))
            ->filter(fn ($values, $dimensionSlug) => is_string($dimensionSlug)
                && preg_match('/^[a-z0-9-]+$/', $dimensionSlug)
                && is_array($values))
            ->take(10)
            ->map(fn (array $values) => collect($values)
                ->filter(fn ($value) => is_string($value) && preg_match('/^[a-z0-9-]+$/', $value))
                ->unique()
                ->take(20)
                ->values());

        foreach ($facetSelections as $dimensionSlug => $categorySlugs) {
            if ($categorySlugs->isEmpty()) {
                continue;
            }

            $query->whereHas('categories', function ($categories) use ($dimensionSlug, $categorySlugs): void {
                $categories
                    ->whereIn('categories.slug', $categorySlugs)
                    ->whereHas('dimension', fn ($dimension) => $dimension->where('slug', $dimensionSlug));
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
        $sort = $request->input('orden', 'todas');
        match ($sort) {
            'mas_votadas' => $query->orderByDesc('votes_count')->orderByDesc('average_rating'),
            'tendencia' => $query->orderByDesc('innovation_score')->orderByDesc('votes_count'),
            'mas_comentadas' => $query->withCount('comments')->orderByDesc('comments_count'),
            'implementadas' => $query->where('status', 'implementada')->orderByDesc('implemented_at')->orderByDesc('created_at'),
            'mejor_valoradas' => $query->orderByDesc('average_rating')->orderByDesc('votes_count'),
            'recientes' => $query->latest(),
            default => $query->orderBy('title'),
        };

        $ideas = $query->paginate(9)->withQueryString();

        $categories = Category::withCount(['ideas' => function ($q) {
            $q->communityPublished();
        }])->get();

        $categoryDimensions = CategoryDimension::query()
            ->active()
            ->ordered()
            ->whereHas('categories', fn ($categoriesQuery) => $categoriesQuery->where('is_active', true))
            ->with(['categories' => fn ($categoriesQuery) => $categoriesQuery
                ->where('is_active', true)
                ->withCount(['classifiedIdeas as community_ideas_count' => fn ($ideasQuery) => $ideasQuery->communityPublished()])
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->get();

        $tags = Tag::withCount(['ideas' => fn ($q) => $q->published()])
            ->orderByDesc('ideas_count')
            ->take(15)
            ->get();

        $departments = User::whereNotNull('department')
            ->distinct()
            ->pluck('department');

        return view('ideas.index', compact('ideas', 'categories', 'categoryDimensions', 'tags', 'departments', 'sort'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(
        Request $request,
        IdeaCommunityService $communityService,
        GlobalIdeaSearchService $ideaSearch
    ): View {
        $categories = Category::where('is_active', true)
            ->whereHas('dimension', fn ($query) => $query->where('is_primary', true)->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $categoryDimensions = $this->activeCategoryDimensions();
        $parentCandidates = Idea::query()
            ->where('user_id', auth()->id())
            ->whereNotIn('workspace_status', ['archivada', 'descartada'])
            ->whereNotIn('status', ['archivada', 'descartada'])
            ->orderBy('title')
            ->with(['category', 'tags'])
            ->get();
        $selectedParentId = $parentCandidates->contains('id', $request->integer('parent'))
            ? $request->integer('parent')
            : null;
        $communityUnits = $communityService->availableUnitsFor($request->user());
        $relationCandidates = $ideaSearch->accessibleCandidates($request->user(), 0)
            ->reject(fn (Idea $candidate) => in_array($candidate->workspace_status, ['archivada', 'descartada'], true)
                || in_array($candidate->status, ['archivada', 'descartada'], true));

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

        return view('ideas.create', compact(
            'categories',
            'categoryDimensions',
            'parentCandidates',
            'selectedParentId',
            'communityUnits',
            'relationCandidates',
            'allTags',
            'popularTags',
            'tags'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StoreIdeaRequest $request,
        IdeaClassificationService $classificationService,
        IdeaHierarchyService $hierarchyService,
        IdeaCommunityService $communityService,
        IdeaRelationFormService $relationFormService
    ): RedirectResponse {
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
                'access_scope' => $request->input('access_scope', 'only_me'),
                'workspace_status' => $request->input('workspace_status', 'capturada'),
                'allow_task_collaboration' => $request->boolean('allow_task_collaboration'),
                'publication_status' => 'not_submitted',
                'community_display' => 'hidden',
                'requested_community_display' => $request->filled('parent_idea_id')
                    ? 'represented_by_parent'
                    : 'standalone',
            ]);

            $classificationService->sync(
                $idea,
                $request->input('classifications', []),
                $request->integer('category_id'),
            );

            if ($request->filled('parent_idea_id')) {
                $hierarchyService->move(
                    $idea,
                    Idea::findOrFail($request->integer('parent_idea_id')),
                    $request->user(),
                    'Jerarquía definida al crear la idea.',
                );
            }

            $idea->refresh();
            $communityService->syncAudience(
                $idea,
                $request->user(),
                $request->filled('organizational_unit_id') ? $request->integer('organizational_unit_id') : null,
                $request->boolean('include_descendants'),
            );

            // Handle Tags (create or normalize to existing, support comma-separated items)
            if ($request->filled('tags')) {
                $tagIds = [];
                foreach ($request->tags as $tagItem) {
                    $exploded = explode(',', (string) $tagItem);
                    foreach ($exploded as $rawName) {
                        $tagName = trim(ltrim(trim($rawName), '#'));
                        if ($tagName !== '') {
                            $tag = Tag::findOrCreateNormalized($tagName);
                            $tagIds[] = $tag->id;
                        }
                    }
                }
                $idea->tags()->sync(array_values(array_unique($tagIds)));
            }

            if ($request->boolean('idea_relations_present') || $request->has('idea_relations')) {
                $relationFormService->sync(
                    $idea,
                    $request->user(),
                    $request->input('idea_relations', []),
                );
            }

            // Handle Attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('idea_attachments/'.$idea->id, 'public');
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
                'workflow' => 'workspace',
                'old_status' => null,
                'new_status' => $idea->workspace_status,
                'comment' => $idea->visibility === 'draft'
                    ? 'Borrador inicial guardado.'
                    : 'Idea registrada en el espacio privado de trabajo.',
            ]);

            IdeaStatusHistory::create([
                'idea_id' => $idea->id,
                'user_id' => auth()->id(),
                'workflow' => 'access',
                'old_status' => null,
                'new_status' => $idea->access_scope,
                'comment' => match ($idea->access_scope) {
                    'profile' => 'La idea se compartió inicialmente desde el perfil de su autor.',
                    'organization' => 'La idea se compartió inicialmente en una comunidad interna.',
                    default => 'La idea se registró con acceso exclusivo para su autor.',
                },
            ]);

            $idea->recalculateRatingAndScore();

            DB::commit();

            $message = match (true) {
                $idea->visibility === 'draft' => 'Borrador guardado correctamente. Puedes completarlo cuando estés listo.',
                $idea->access_scope === 'profile' => 'Idea guardada y visible desde tu perfil. Cuando esté lista también podrás solicitar su publicación.',
                $idea->access_scope === 'organization' => 'Idea guardada y compartida en la comunidad interna seleccionada.',
                default => 'Idea guardada sólo para ti. Cuando esté lista podrás compartirla o solicitar su publicación.',
            };

            return redirect()->route('ideas.show', $idea->slug)->with('success', $message);
        } catch (ValidationException $e) {
            DB::rollBack();

            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Ocurrió un error al registrar la idea: '.$e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(
        string $slug,
        IdeaTreeService $treeService
    ): View {
        $idea = Idea::with([
            'user',
            'category',
            'categories.dimension',
            'tags',
            'attachments',
            'statusHistories.user',
            'parentIdea.user',
            'children.user',
            'children.category',
            'tasks.assignee',
            'outgoingRelations.targetIdea.user',
            'outgoingRelations.createdBy',
            'outgoingRelations.reviewedBy',
            'incomingRelations.sourceIdea.user',
            'incomingRelations.createdBy',
            'incomingRelations.reviewedBy',
            'comments' => function ($query) {
                $query->whereNull('parent_id')
                    ->with(['user', 'likes', 'replies.user', 'replies.likes'])
                    ->latest();
            },
        ])->where('slug', $slug)->firstOrFail();

        $this->authorize('view', $idea);

        $viewer = auth()->user();
        $idea->setRelation('children', $idea->children
            ->filter(fn (Idea $child) => $viewer?->can('view', $child))
            ->values());
        $idea->setRelation('tasks', $idea->tasks
            ->filter(fn (Task $task) => $viewer?->can('view', $task))
            ->values());

        if ($idea->parentIdea && ! $viewer?->can('view', $idea->parentIdea)) {
            $idea->unsetRelation('parentIdea');
        }

        $idea->setRelation('outgoingRelations', $idea->outgoingRelations
            ->filter(fn ($relation) => ($relation->status === 'approved' || $viewer?->can('update', $relation))
                && $relation->targetIdea
                && $viewer?->can('view', $relation->targetIdea))
            ->values());
        $idea->setRelation('incomingRelations', $idea->incomingRelations
            ->filter(fn ($relation) => $relation->status === 'approved'
                && $relation->sourceIdea
                && $viewer?->can('view', $relation->sourceIdea))
            ->values());

        // Increment views count safely
        $sessionKey = 'viewed_idea_'.$idea->id;
        if (! session()->has($sessionKey)) {
            $idea->increment('views_count');
            session()->put($sessionKey, true);
            $idea->recalculateRatingAndScore();
        }

        // Related ideas in the same category
        $relatedIdeas = Idea::with(['user', 'category'])
            ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $idea->categories->modelKeys()))
            ->where('id', '!=', $idea->id)
            ->published()
            ->orderByDesc('innovation_score')
            ->take(3)
            ->get();

        $pendingRelationReviews = collect();

        if ($viewer) {
            $pendingRelationReviews = $idea->incomingRelations()
                ->where('status', 'pending')
                ->when(! $viewer->isAdmin(), fn ($query) => $query->whereHas('targetIdea', fn ($target) => $target->where('user_id', $viewer->id)))
                ->with(['sourceIdea.user', 'createdBy', 'reviewedBy'])
                ->get();
        }

        $traceRoot = $idea->rootIdea();
        $traceRoot->loadMissing(['category', 'tags']);
        $traceIdeas = collect([$traceRoot]);
        $pendingParentIds = collect([$traceRoot->id]);
        $visitedIds = [$traceRoot->id => true];

        while ($pendingParentIds->isNotEmpty() && $traceIdeas->count() < 250) {
            $remainingNodes = 250 - $traceIdeas->count();
            $level = Idea::with(['category', 'tags'])
                ->whereIn('parent_idea_id', $pendingParentIds)
                ->orderBy('title')
                ->limit($remainingNodes)
                ->get();

            $unvisitedLevel = $level->reject(fn (Idea $node) => isset($visitedIds[$node->id]));

            foreach ($unvisitedLevel as $node) {
                $visitedIds[$node->id] = true;
            }

            $pendingParentIds = $unvisitedLevel->pluck('id');

            $traceIdeas = $traceIdeas->concat(
                $unvisitedLevel->filter(fn (Idea $node) => $viewer?->can('view', $node))
            );
        }

        $traceTree = $treeService->prepare($traceIdeas);

        return view('ideas.show', compact(
            'idea',
            'relatedIdeas',
            'pendingRelationReviews',
            'traceIdeas',
            'traceRoot',
            'traceTree'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(
        Idea $idea,
        IdeaClassificationService $classificationService,
        IdeaCommunityService $communityService,
        IdeaHierarchyService $hierarchyService,
        GlobalIdeaSearchService $ideaSearch
    ): View {
        $this->authorize('update', $idea);

        $categories = Category::where('is_active', true)
            ->whereHas('dimension', fn ($query) => $query->where('is_primary', true)->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $categoryDimensions = $this->activeCategoryDimensions();
        $excludedParentIds = $hierarchyService->descendantIds($idea)->push($idea->id);
        $parentCandidates = auth()->user()->ideas()
            ->whereNotIn('id', $excludedParentIds)
            ->whereNotIn('workspace_status', ['archivada', 'descartada'])
            ->whereNotIn('status', ['archivada', 'descartada'])
            ->orderBy('title')
            ->with(['category', 'tags'])
            ->get();
        $communityUnits = $communityService->availableUnitsFor(auth()->user());
        $selectedCommunityShare = $idea->communityUnits()->first();
        $selectedCommunityUnitId = $selectedCommunityShare?->id;
        $selectedCommunityIncludesDescendants = (bool) $selectedCommunityShare?->pivot?->include_descendants;
        $relationCandidates = $ideaSearch->accessibleCandidates(auth()->user(), $idea->id)
            ->reject(fn (Idea $candidate) => in_array($candidate->workspace_status, ['archivada', 'descartada'], true)
                || in_array($candidate->status, ['archivada', 'descartada'], true));

        $existingRelations = $idea->outgoingRelations()
            ->with(['targetIdea.user', 'createdBy', 'reviewedBy'])
            ->orderBy('created_at')
            ->get();

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

        $selectedClassifications = $classificationService->currentSelections($idea->loadMissing('categories'));

        return view('ideas.edit', compact(
            'idea',
            'categories',
            'categoryDimensions',
            'parentCandidates',
            'communityUnits',
            'selectedCommunityUnitId',
            'selectedCommunityIncludesDescendants',
            'relationCandidates',
            'existingRelations',
            'allTags',
            'popularTags',
            'tags',
            'selectedTags',
            'selectedClassifications'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateIdeaRequest $request,
        Idea $idea,
        IdeaClassificationService $classificationService,
        IdeaHierarchyService $hierarchyService,
        IdeaCommunityService $communityService,
        IdeaRelationFormService $relationFormService,
        IdeaStatusCascadeService $statusCascadeService
    ): RedirectResponse {
        $this->authorize('update', $idea);

        DB::beginTransaction();
        try {
            $oldWorkspaceStatus = $idea->workspace_status;
            $newWorkspaceStatus = $request->input('workspace_status', $oldWorkspaceStatus);
            $oldAccessScope = $idea->access_scope;
            $newAccessScope = $request->input('access_scope', $oldAccessScope);

            $idea->update([
                'title' => $request->title,
                'summary' => Str::limit(strip_tags($request->description), 160),
                'description' => $request->description,
                'problem_opportunity' => $request->problem_opportunity,
                'category_id' => $request->category_id,
                'visibility' => $idea->isPublished() ? 'public' : $request->visibility,
                'access_scope' => $newAccessScope,
                'workspace_status' => $idea->isPublished() ? $oldWorkspaceStatus : $newWorkspaceStatus,
                'allow_task_collaboration' => $request->boolean('allow_task_collaboration'),
            ]);

            $classificationService->sync(
                $idea,
                $request->input('classifications', []),
                $request->integer('category_id'),
            );

            if ($request->has('parent_idea_id')) {
                $parent = $request->filled('parent_idea_id')
                    ? Idea::findOrFail($request->integer('parent_idea_id'))
                    : null;
                $hierarchyService->move($idea, $parent, $request->user(), 'Jerarquía actualizada desde la edición de la idea.');
            }

            $idea->refresh();
            $communityService->syncAudience(
                $idea,
                $request->user(),
                $request->filled('organizational_unit_id') ? $request->integer('organizational_unit_id') : null,
                $request->boolean('include_descendants'),
            );

            if (! $idea->isPublished() && $oldWorkspaceStatus !== $newWorkspaceStatus) {
                IdeaStatusHistory::create([
                    'idea_id' => $idea->id,
                    'user_id' => auth()->id(),
                    'workflow' => 'workspace',
                    'old_status' => $oldWorkspaceStatus,
                    'new_status' => $newWorkspaceStatus,
                    'comment' => 'Estado de trabajo privado actualizado por el autor.',
                ]);

                $statusCascadeService->cascadeTerminalStatus($idea, $newWorkspaceStatus, $request->user());
            }

            if ($oldAccessScope !== $newAccessScope) {
                IdeaStatusHistory::create([
                    'idea_id' => $idea->id,
                    'user_id' => auth()->id(),
                    'workflow' => 'access',
                    'old_status' => $oldAccessScope,
                    'new_status' => $newAccessScope,
                    'comment' => match ($newAccessScope) {
                        'profile' => 'La idea ahora es visible desde el perfil de su autor.',
                        'organization' => 'La idea ahora se comparte en una comunidad interna.',
                        default => 'El acceso a la idea quedó restringido a su autor.',
                    },
                ]);
            }

            // Sync Tags (create or normalize to existing, support comma-separated items)
            if ($request->filled('tags')) {
                $tagIds = [];
                foreach ($request->tags as $tagItem) {
                    $exploded = explode(',', (string) $tagItem);
                    foreach ($exploded as $rawName) {
                        $tagName = trim(ltrim(trim($rawName), '#'));
                        if ($tagName !== '') {
                            $tag = Tag::findOrCreateNormalized($tagName);
                            $tagIds[] = $tag->id;
                        }
                    }
                }
                $idea->tags()->sync(array_values(array_unique($tagIds)));
            } else {
                $idea->tags()->detach();
            }

            if ($request->boolean('idea_relations_present') || $request->has('idea_relations')) {
                $relationFormService->sync(
                    $idea,
                    $request->user(),
                    $request->input('idea_relations', []),
                );
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
                    $path = $file->store('idea_attachments/'.$idea->id, 'public');
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
        } catch (ValidationException $e) {
            DB::rollBack();

            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Error al actualizar la idea: '.$e->getMessage());
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

        if (! auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Debes iniciar sesión para votar.'], 401);
            }

            return redirect()->route('login');
        }

        if (! $request->user()->can('vote', $idea)) {
            $message = $idea->user_id === auth()->id()
                ? 'No puedes votar por tu propia idea.'
                : ($idea->parent_idea_id
                    ? 'Las valoraciones se concentran en la idea madre.'
                    : 'Esta idea no admite votaciones en su estado o nivel de acceso actual.');

            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 422);
            }

            return back()->with('error', $message);
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
                'rating_context' => $idea->hasPreliminaryRatings() ? 'preliminary' : 'community',
            ]);
        }

        return back()->with('success', '¡Tu valoración de '.$request->rating.' estrellas ha sido registrada!');
    }

    /**
     * Toggle Favorite / Bookmark.
     */
    public function toggleFavorite(Idea $idea): JsonResponse|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $this->authorize('view', $idea);

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

    private function activeCategoryDimensions(): Collection
    {
        return CategoryDimension::query()
            ->active()
            ->ordered()
            ->with(['categories' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['parent.parent'])
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->get();
    }
}
