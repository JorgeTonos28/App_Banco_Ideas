<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Regional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminRegionalController extends Controller
{
    /**
     * Display a listing of regionals.
     */
    public function index(Request $request): View
    {
        $regionals = Regional::withCount('users')
            ->orderBy('order')
            ->orderBy('code')
            ->get();

        return view('admin.regionals.index', compact('regionals'));
    }

    /**
     * Store a newly created regional.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:regionals,code'],
            'name' => ['required', 'string', 'max:100'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['order'] = $validated['order'] ?? (Regional::max('order') + 1);
        $validated['is_active'] = true;

        Regional::create($validated);

        return back()->with('success', "Regional {$validated['code']} - {$validated['name']} creada exitosamente.");
    }

    /**
     * Update the specified regional.
     */
    public function update(Request $request, Regional $regional): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('regionals', 'code')->ignore($regional->id)],
            'name' => ['required', 'string', 'max:100'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        $regional->update($validated);

        return back()->with('success', "Regional {$regional->full_name} actualizada exitosamente.");
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(Regional $regional): RedirectResponse
    {
        $regional->update(['is_active' => !$regional->is_active]);

        $statusText = $regional->is_active ? 'habilitada' : 'inhabilitada';
        return back()->with('success', "La regional {$regional->full_name} ha sido {$statusText}.");
    }

    /**
     * Delete regional if no users attached.
     */
    public function destroy(Regional $regional): RedirectResponse
    {
        if ($regional->users()->count() > 0) {
            return back()->with('error', "No se puede eliminar la regional {$regional->code} porque tiene colaboradores vinculados. Puedes inhabilitarla.");
        }

        $regional->delete();

        return back()->with('success', "Regional eliminada correctamente.");
    }
}
