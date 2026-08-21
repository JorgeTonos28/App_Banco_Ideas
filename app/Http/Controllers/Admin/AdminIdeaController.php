<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Idea\AdminUpdateIdeaRequest;
use App\Models\Category;
use App\Models\Idea;
use App\Models\IdeaStatusHistory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminIdeaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Idea::with(['user', 'category', 'assignedTo']);

        // Search
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filters
        if ($status = $request->input('estado')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->input('categoria')) {
            $query->where('category_id', $categoryId);
        }

        if ($priority = $request->input('prioridad')) {
            $query->where('priority', $priority);
        }

        if ($assignedId = $request->input('responsable')) {
            $query->where('assigned_to_user_id', $assignedId);
        }

        $ideas = $query->latest()->paginate(15)->withQueryString();

        $categories = Category::all();
        $users = User::where('is_active', true)->get();

        return view('admin.ideas.index', compact('ideas', 'categories', 'users'));
    }

    public function show(Idea $idea): JsonResponse
    {
        $idea->load(['user', 'category', 'tags', 'assignedTo', 'statusHistories.user']);
        return response()->json($idea);
    }

    public function update(AdminUpdateIdeaRequest $request, Idea $idea): RedirectResponse|JsonResponse
    {
        DB::beginTransaction();
        try {
            $oldStatus = $idea->status;
            $newStatus = $request->status;

            $idea->update([
                'status' => $newStatus,
                'assigned_to_user_id' => $request->assigned_to_user_id,
                'priority' => $request->priority,
                'category_id' => $request->category_id ?? $idea->category_id,
                'admin_observations' => $request->admin_observations,
                'next_action' => $request->next_action,
                'follow_up_date' => $request->follow_up_date,
                'is_featured' => $request->boolean('is_featured'),
                'implemented_at' => $newStatus === 'implementada' && !$idea->implemented_at ? now() : $idea->implemented_at,
            ]);

            // If status changed, create StatusHistory record
            if ($oldStatus !== $newStatus || $request->filled('status_comment')) {
                IdeaStatusHistory::create([
                    'idea_id' => $idea->id,
                    'user_id' => auth()->id(),
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'comment' => $request->status_comment ?: 'Estado actualizado por el equipo de innovación.',
                ]);
            }

            $idea->recalculateRatingAndScore();

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Idea actualizada exitosamente.',
                    'idea' => $idea,
                ]);
            }

            return back()->with('success', 'Gestión de la idea guardada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Error al guardar los cambios administrativos: ' . $e->getMessage());
        }
    }

    public function toggleFeatured(Idea $idea): JsonResponse|RedirectResponse
    {
        $idea->update(['is_featured' => !$idea->is_featured]);

        if (request()->expectsJson()) {
            return response()->json([
                'is_featured' => $idea->is_featured,
                'message' => $idea->is_featured ? 'Idea marcada como destacada.' : 'Idea removida de destacadas.',
            ]);
        }

        return back()->with('success', 'Estado destacado actualizado.');
    }

    public function batchAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => ['required', 'in:set_status,archive,feature,unfeature'],
            'idea_ids' => ['required', 'array'],
            'idea_ids.*' => ['integer', 'exists:ideas,id'],
            'new_status' => ['nullable', 'in:nueva,en_revision,priorizada,en_desarrollo,implementada,descartada,archivada'],
        ]);

        $action = $request->action;
        $ids = $request->idea_ids;

        match ($action) {
            'set_status' => Idea::whereIn('id', $ids)->update(['status' => $request->new_status]),
            'archive' => Idea::whereIn('id', $ids)->update(['status' => 'archivada']),
            'feature' => Idea::whereIn('id', $ids)->update(['is_featured' => true]),
            'unfeature' => Idea::whereIn('id', $ids)->update(['is_featured' => false]),
        };

        return back()->with('success', 'Acción masiva ejecutada con éxito en ' . count($ids) . ' ideas.');
    }
}
