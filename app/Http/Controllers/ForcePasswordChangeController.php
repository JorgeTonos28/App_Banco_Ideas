<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    /**
     * Show mandatory password change screen.
     */
    public function show(): View|RedirectResponse
    {
        if (!auth()->user()->must_change_password) {
            return redirect()->route('my-ideas.index');
        }

        return view('auth.force-password-change');
    }

    /**
     * Process mandatory password update.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed', 'different:current_password'],
        ], [
            'password.different' => 'La nueva contraseña debe ser diferente a la contraseña temporal asignada.',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect()->route('my-ideas.index')->with('success', 'Contraseña personalizada guardada con éxito. Ya puedes utilizar la plataforma.');
    }
}
