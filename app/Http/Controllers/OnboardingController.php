<?php

namespace App\Http\Controllers;

use App\Models\Regional;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    /**
     * Show the onboarding activation form.
     */
    public function show(string $token): View|RedirectResponse
    {
        $invitation = UserInvitation::where('token', $token)->first();

        if (! $invitation) {
            return redirect()->route('login')->with('error', 'El enlace de invitación no es válido.');
        }

        if ($invitation->isCompleted()) {
            return redirect()->route('login')->with('status', 'Esta invitación ya fue activada. Por favor inicia sesión con tu contraseña.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('login')->with('error', 'Este enlace de invitación ha expirado. Solicita a un administrador el reenvío de la invitación.');
        }

        $regionals = Regional::where('is_active', true)->where('type', 'regional')->orderBy('order')->get();
        $organizationalUnits = Regional::where('is_active', true)->with('parent')->get()->sortBy('path_label')->values();

        return view('onboarding.activate', compact('invitation', 'regionals', 'organizationalUnits'));
    }

    /**
     * Complete onboarding and create/activate user.
     */
    public function activate(Request $request, string $token): RedirectResponse
    {
        $invitation = UserInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isCompleted() || $invitation->isExpired()) {
            return redirect()->route('login')->with('error', 'La invitación ya no está disponible.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'regional_id' => ['nullable', 'exists:regionals,id'],
            'organizational_unit_id' => ['nullable', 'exists:regionals,id'],
            'bio' => ['nullable', 'string', 'max:500'],
        ]);

        $unitId = $request->input('organizational_unit_id', $invitation->organizational_unit_id);
        $organizationalUnit = $unitId
            ? Regional::find($unitId)
            : ($request->filled('regional_id') ? Regional::find($request->regional_id) : $invitation->regional);
        $regional = $organizationalUnit?->type === 'regional'
            ? $organizationalUnit
            : $organizationalUnit?->ancestors()->first(fn (Regional $ancestor) => $ancestor->type === 'regional');

        $user = User::create([
            'name' => $request->name,
            'email' => $invitation->email,
            'password' => Hash::make($request->password),
            'role' => $invitation->role,
            'job_title' => $request->job_title ?? $invitation->job_title,
            'department' => $request->department ?? $invitation->department,
            'regional_id' => $regional?->id,
            'organizational_unit_id' => $organizationalUnit?->id,
            'regional' => $regional?->full_name,
            'bio' => $request->bio,
            'is_active' => true,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ]);

        $invitation->update([
            'registered_at' => now(),
        ]);

        Auth::login($user);

        return redirect()->route('my-ideas.index')->with('success', '¡Bienvenido a INNOVATEP Ideas! Tu cuenta institucional ha sido configurada y activada exitosamente.');
    }
}
