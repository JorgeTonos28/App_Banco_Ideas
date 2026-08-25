<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationalUnitRequest;
use App\Http\Requests\Organization\UpdateOrganizationalUnitRequest;
use App\Models\Regional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRegionalController extends Controller
{
    /**
     * Display a listing of regionals.
     */
    public function index(Request $request): View
    {
        $regionals = Regional::with(['parent', 'children'])
            ->withCount(['users', 'members', 'children'])
            ->orderBy('order')
            ->orderBy('code')
            ->get();
        $treeRoots = $regionals->whereNull('parent_id');
        $treeByParent = $regionals->whereNotNull('parent_id')->groupBy('parent_id');

        return view('admin.regionals.index', compact('regionals', 'treeRoots', 'treeByParent'));
    }

    /**
     * Store a newly created regional.
     */
    public function store(StoreOrganizationalUnitRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['order'] = $validated['order'] ?? (Regional::max('order') + 1);
        $validated['is_active'] = true;

        $unit = Regional::create($validated);

        return back()->with('success', "{$unit->type_label} {$unit->full_name} creada correctamente.");
    }

    /**
     * Update the specified regional.
     */
    public function update(UpdateOrganizationalUnitRequest $request, Regional $regional): RedirectResponse
    {
        $regional->update($request->validated());

        return back()->with('success', "{$regional->type_label} {$regional->full_name} actualizada correctamente.");
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(Regional $regional): RedirectResponse
    {
        if ($regional->is_active && $regional->children()->where('is_active', true)->exists()) {
            return back()->with('error', 'Inhabilita primero las unidades dependientes activas.');
        }

        $regional->update(['is_active' => ! $regional->is_active]);

        $statusText = $regional->is_active ? 'habilitada' : 'inhabilitada';

        return back()->with('success', "La regional {$regional->full_name} ha sido {$statusText}.");
    }

    /**
     * Delete regional if no users attached.
     */
    public function destroy(Regional $regional): RedirectResponse
    {
        if ($regional->children()->exists()) {
            return back()->with('error', 'No se puede eliminar una unidad que todavía tiene niveles dependientes.');
        }

        if ($regional->members()->exists() || $regional->users()->exists()) {
            return back()->with('error', "No se puede eliminar {$regional->full_name} porque tiene colaboradores vinculados. Puedes inhabilitarla.");
        }

        $regional->delete();

        return back()->with('success', 'Regional eliminada correctamente.');
    }
}
