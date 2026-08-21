<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::withCount('ideas');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('job_title', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('rol')) {
            $query->where('role', $role);
        }

        if ($request->has('estado') && $request->input('estado') !== '') {
            $query->where('is_active', $request->boolean('estado'));
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'in:user,admin'],
        ]);

        if ($user->id === auth()->id() && $request->role !== 'admin') {
            return back()->with('error', 'No puedes revocar tus propios permisos de administrador.');
        }

        $user->update(['role' => $request->role]);

        return back()->with('success', 'Rol de usuario actualizado correctamente.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta de usuario.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $statusText = $user->is_active ? 'activada' : 'desactivada';
        return back()->with('success', "La cuenta de {$user->name} ha sido {$statusText}.");
    }
}
