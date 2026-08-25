<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivateInvitationRequest;
use App\Models\Regional;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        $invitation->loadMissing(['organizationalUnit', 'regional']);
        $organizationalUnits = Regional::where('is_active', true)->with('parent')->get()->sortBy('path_label')->values();

        return view('onboarding.activate', compact('invitation', 'organizationalUnits'));
    }

    /**
     * Complete onboarding and create/activate user.
     */
    public function activate(ActivateInvitationRequest $request, string $token): RedirectResponse
    {
        $invitation = UserInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isCompleted() || $invitation->isExpired()) {
            return redirect()->route('login')->with('error', 'La invitación ya no está disponible.');
        }

        $validated = $request->validated();

        $unitId = $invitation->organizational_unit_id
            ?? $invitation->regional_id
            ?? ($validated['organizational_unit_id'] ?? null);
        $organizationalUnit = $unitId
            ? Regional::find($unitId)
            : null;
        $regional = $organizationalUnit?->type === 'regional'
            ? $organizationalUnit
            : $organizationalUnit?->ancestors()->first(fn (Regional $ancestor) => $ancestor->type === 'regional');

        $user = User::create([
            'name' => $validated['name'],
            'email' => $invitation->email,
            'password' => Hash::make($validated['password']),
            'role' => $invitation->role,
            'job_title' => $validated['job_title'] ?? $invitation->job_title,
            'department' => $validated['department'] ?? $invitation->department,
            'regional_id' => $regional?->id,
            'organizational_unit_id' => $organizationalUnit?->id,
            'regional' => $regional?->full_name,
            'bio' => $validated['bio'] ?? null,
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
