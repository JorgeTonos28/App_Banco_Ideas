<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    /**
     * Show 2FA Security management in profile.
     */
    public function showSecurity(): View
    {
        $user = auth()->user();
        $secret = session('2fa_setup_secret') ?? TwoFactorService::generateSecretKey();
        session(['2fa_setup_secret' => $secret]);

        $otpAuthUrl = TwoFactorService::getOtpAuthUrl($user, $secret);
        $qrCodeUrl = TwoFactorService::getQrCodeUrl($otpAuthUrl);

        return view('profile.security', compact('user', 'secret', 'otpAuthUrl', 'qrCodeUrl'));
    }

    /**
     * Enable TOTP Authenticator 2FA.
     */
    public function enableTotp(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'secret' => ['required', 'string'],
        ]);

        $user = auth()->user();
        $secret = $request->secret;

        if (!TwoFactorService::verifyTotpCode($secret, $request->code)) {
            return back()->with('error', 'El código de 6 dígitos introducido es incorrecto o ha expirado. Verifica la hora de tu dispositivo.');
        }

        // Generate 8 backup recovery codes
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = strtoupper(Str::random(10));
        }

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_type' => 'totp',
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);

        session()->forget('2fa_setup_secret');

        return back()->with('success', '¡Autenticación de Dos Factores (App Authenticator) activada exitosamente!');
    }

    /**
     * Enable Email OTP 2FA.
     */
    public function enableEmail(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // Generate and store code
        $code = TwoFactorService::generateEmailCode();
        $user->update([
            'two_factor_code' => $code,
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);

        // In a live SMTP environment, Mail::to($user->email)->send(...)
        // Store code in session flash for local demonstration
        session()->flash('demo_2fa_code', $code);

        return back()->with('email_2fa_requested', true)
                     ->with('success', "Se ha enviado un código de verificación a {$user->email} (Código de prueba: {$code}).");
    }

    /**
     * Confirm and finalize Email OTP 2FA activation.
     */
    public function confirmEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = auth()->user();

        if ($user->two_factor_code !== $request->code || now()->isAfter($user->two_factor_expires_at)) {
            return back()->with('error', 'El código de verificación por correo es incorrecto o ha expirado.');
        }

        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = strtoupper(Str::random(10));
        }

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_type' => 'email',
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);

        return back()->with('success', '¡Autenticación de Dos Factores por Correo Institucional activada exitosamente!');
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $user = auth()->user();
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_type' => null,
            'two_factor_secret' => null,
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return back()->with('success', 'La autenticación de dos pasos ha sido desactivada.');
    }

    /**
     * Show 2FA Challenge screen during login.
     */
    public function showChallenge(): View|RedirectResponse
    {
        $userId = session('2fa:user:id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user || !$user->two_factor_enabled) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge', compact('user'));
    }

    /**
     * Verify 2FA Challenge code on login.
     */
    public function verifyChallenge(Request $request): RedirectResponse
    {
        $userId = session('2fa:user:id');
        $remember = session('2fa:remember', false);

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = trim($request->code);
        $isValid = false;

        // 1. Check if it's a recovery code
        if (is_array($user->two_factor_recovery_codes) && in_array(strtoupper($code), $user->two_factor_recovery_codes)) {
            $isValid = true;
            // Remove used recovery code
            $updatedCodes = array_diff($user->two_factor_recovery_codes, [strtoupper($code)]);
            $user->update(['two_factor_recovery_codes' => array_values($updatedCodes)]);
        }
        // 2. Check TOTP
        elseif ($user->two_factor_type === 'totp' && $user->two_factor_secret) {
            $isValid = TwoFactorService::verifyTotpCode($user->two_factor_secret, $code);
        }
        // 3. Check Email OTP
        elseif ($user->two_factor_type === 'email') {
            if ($user->two_factor_code === $code && $user->two_factor_expires_at && now()->isBefore($user->two_factor_expires_at)) {
                $isValid = true;
                $user->update(['two_factor_code' => null, 'two_factor_expires_at' => null]);
            }
        }

        if (!$isValid) {
            return back()->with('error', 'El código de seguridad 2FA introducido es inválido o ha caducado.');
        }

        // Clear 2FA session & complete login
        session()->forget(['2fa:user:id', '2fa:remember']);
        Auth::login($user, $remember);
        $request->session()->regenerate();

        if ($user->must_change_password) {
            return redirect()->route('password.force-change');
        }

        return redirect()->intended(route('my-ideas.index'))->with('success', "¡Bienvenido de nuevo, {$user->name}!");
    }

    /**
     * Update user password from security settings.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers()->mixedCase(), 'confirmed', 'different:current_password'],
        ], [
            'password.different' => 'La nueva contraseña debe ser diferente a tu contraseña actual.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'current_password.current_password' => 'La contraseña actual no coincide con nuestros registros.',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return back()->with('success', 'Tu contraseña ha sido actualizada con éxito.');
    }

    /**
     * Resend Email 2FA code during login.
     */
    public function resendEmailCode(): RedirectResponse
    {
        $userId = session('2fa:user:id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);
        if ($user->two_factor_type !== 'email') {
            return back();
        }

        $code = TwoFactorService::generateEmailCode();
        $user->update([
            'two_factor_code' => $code,
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);

        session()->flash('demo_2fa_code', $code);

        return back()->with('success', "Nuevo código enviado a {$user->email} (Código de prueba: {$code}).");
    }
}
