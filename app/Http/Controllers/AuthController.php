<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return back()->withErrors([
                'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
            ])->onlyInput('email');
        }

        if (!$user->is_active) {
            return back()->withErrors([
                'email' => 'Tu cuenta ha sido desactivada. Por favor contacta al administrador.',
            ]);
        }

        // Check if Two-Factor Authentication is enabled
        if ($user->two_factor_enabled) {
            session([
                '2fa:user:id' => $user->id,
                '2fa:remember' => $remember,
            ]);

            if ($user->two_factor_type === 'email') {
                $code = TwoFactorService::generateEmailCode();
                $user->update([
                    'two_factor_code' => $code,
                    'two_factor_expires_at' => now()->addMinutes(10),
                ]);
                session()->flash('demo_2fa_code', $code);
            }

            return redirect()->route('2fa.challenge');
        }

        // Standard Login
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $user->updateQuietly(['last_activity_at' => now()]);

        if ($user->must_change_password) {
            return redirect()->route('password.force-change');
        }

        return redirect()->intended(route('my-ideas.index'))->with('success', '¡Bienvenido de vuelta a INNOVATEP Ideas, ' . $user->name . '!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Has cerrado sesión exitosamente.');
    }
}
