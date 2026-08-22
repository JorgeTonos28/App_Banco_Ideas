<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Regional;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     * Display a listing of users and pending invitations.
     */
    public function index(Request $request): View
    {
        $query = User::with(['regionalModel'])->withCount('ideas');

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

        if ($regionalId = $request->input('regional_id')) {
            $query->where('regional_id', $regionalId);
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $regionals = Regional::where('is_active', true)->orderBy('order')->get();
        $pendingInvitations = UserInvitation::whereNull('registered_at')->where('expires_at', '>', now())->latest()->get();

        return view('admin.users.index', compact('users', 'regionals', 'pendingInvitations'));
    }

    /**
     * Store a newly created user or dispatch an onboarding invitation.
     */
    public function store(Request $request): RedirectResponse
    {
        $creationType = $request->input('creation_type', 'invitation'); // 'invitation' or 'direct'

        if ($creationType === 'invitation') {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:user_invitations,email'],
                'role' => ['required', 'in:user,admin'],
                'job_title' => ['nullable', 'string', 'max:100'],
                'department' => ['nullable', 'string', 'max:100'],
                'regional_id' => ['nullable', 'exists:regionals,id'],
            ]);

            $token = Str::random(64);
            $invitation = UserInvitation::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'job_title' => $validated['job_title'] ?? null,
                'department' => $validated['department'] ?? null,
                'regional_id' => $validated['regional_id'] ?? null,
                'token' => $token,
                'expires_at' => now()->addHours(72),
            ]);

            $activationLink = route('onboarding.accept', $token);
            session()->flash('invitation_link', $activationLink);

            return redirect()->route('admin.users.index')->with('success', "Invitación de onboarding enviada a {$validated['email']}.");
        }

        // Direct creation with temporary password
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'role' => ['required', 'in:user,admin'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'regional_id' => ['nullable', 'exists:regionals,id'],
            'bio' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $regional = !empty($validated['regional_id']) ? Regional::find($validated['regional_id']) : null;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'job_title' => $validated['job_title'] ?? null,
            'department' => $validated['department'] ?? null,
            'regional_id' => $regional?->id,
            'regional' => $regional?->full_name,
            'bio' => $validated['bio'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'must_change_password' => true, // Enforce password change upon first login
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', "Usuario {$user->name} creado con contraseña temporal. Se le solicitará cambiarla en su primer inicio de sesión.");
    }

    /**
     * Resend an invitation token.
     */
    public function resendInvitation(UserInvitation $invitation): RedirectResponse
    {
        $token = Str::random(64);
        $invitation->update([
            'token' => $token,
            'expires_at' => now()->addHours(72),
        ]);

        $activationLink = route('onboarding.accept', $token);
        session()->flash('invitation_link', $activationLink);

        return back()->with('success', "Invitación reenviada a {$invitation->email}.");
    }

    /**
     * Delete an unaccepted invitation.
     */
    public function cancelInvitation(UserInvitation $invitation): RedirectResponse
    {
        $invitation->delete();
        return back()->with('success', 'Invitación cancelada correctamente.');
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:user,admin'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'regional_id' => ['nullable', 'exists:regionals,id'],
            'bio' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'string', Password::min(8)],
            'is_active' => ['boolean'],
        ]);

        // Security check: Last active admin protection
        if ($user->isAdmin() && ($validated['role'] !== 'admin' || !$request->boolean('is_active', true))) {
            $otherActiveAdmins = User::where('role', 'admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveAdmins < 1) {
                return back()->with('error', 'Acción denegada: Debe existir al menos un administrador general activo en el sistema.');
            }
        }

        $regional = !empty($validated['regional_id']) ? Regional::find($validated['regional_id']) : null;
        $validated['regional'] = $regional?->full_name;

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', "Usuario {$user->name} actualizado exitosamente.");
    }

    /**
     * Update role of a user.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'in:user,admin'],
        ]);

        if ($user->id === auth()->id() && $request->role !== 'admin') {
            return back()->with('error', 'No puedes revocar tus propios permisos de administrador.');
        }

        if ($user->isAdmin() && $request->role !== 'admin') {
            $otherActiveAdmins = User::where('role', 'admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveAdmins < 1) {
                return back()->with('error', 'Acción denegada: Debe existir al menos un administrador activo en el sistema.');
            }
        }

        $user->update(['role' => $request->role]);

        return back()->with('success', 'Rol de usuario actualizado correctamente.');
    }

    /**
     * Toggle the active/inactive status of a user.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta de usuario en sesión.');
        }

        if ($user->is_active && $user->isAdmin()) {
            $otherActiveAdmins = User::where('role', 'admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveAdmins < 1) {
                return back()->with('error', 'Acción denegada: No puedes desactivar al único administrador activo del sistema.');
            }
        }

        $user->update(['is_active' => !$user->is_active]);

        $statusText = $user->is_active ? 'activada' : 'desactivada';
        return back()->with('success', "La cuenta de {$user->name} ha sido {$statusText}.");
    }

    /**
     * Delete a user permanently from the system.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta en sesión.');
        }

        if ($user->isAdmin()) {
            $otherActiveAdmins = User::where('role', 'admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveAdmins < 1) {
                return back()->with('error', 'Acción denegada: No es posible eliminar al único administrador general del sistema.');
            }
        }

        DB::transaction(function () use ($user) {
            $user->ratings()->delete();
            $user->favorites()->delete();
            $user->commentLikes()->delete();
            $user->comments()->delete();

            foreach ($user->ideas as $idea) {
                $idea->attachments()->delete();
                $idea->ratings()->delete();
                $idea->comments()->delete();
                $idea->statusHistories()->delete();
                $idea->tags()->detach();
                $idea->delete();
            }

            $user->delete();
        });

        return redirect()->route('admin.users.index')->with('success', "El usuario {$user->name} ha sido eliminado permanentemente.");
    }
}
