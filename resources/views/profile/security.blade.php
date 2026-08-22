@extends('layouts.app')

@section('title', 'Seguridad y 2FA - Mi Perfil')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="{ 
    method: '{{ $user->two_factor_type ?? 'totp' }}',
    emailCodeSent: {{ session('email_2fa_requested') ? 'true' : 'false' }},
    newPass: '',
    confirmPass: '',
    get hasMinLength() { return this.newPass.length >= 8; },
    get hasUpper() { return /[A-Z]/.test(this.newPass); },
    get hasLower() { return /[a-z]/.test(this.newPass); },
    get hasNumber() { return /[0-9]/.test(this.newPass); },
    get passwordsMatch() { return this.newPass.length > 0 && this.newPass === this.confirmPass; }
}">

    <!-- Header & Profile Navigation Tabs -->
    <div class="space-y-4">
        <div>
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">Seguridad y Credenciales</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Administra tu contraseña institucional y la protección de doble factor de autenticación (2FA)</p>
        </div>

        <div class="flex items-center gap-3 border-b border-surface-container-high pb-px text-xs font-semibold">
            <a href="{{ route('profile.edit') }}" class="py-2.5 px-3 text-on-surface-variant hover:text-on-surface border-b-2 border-transparent">
                Información Personal
            </a>
            <a href="{{ route('profile.security') }}" class="py-2.5 px-3 text-primary font-bold border-b-2 border-primary">
                Seguridad & 2FA
            </a>
        </div>
    </div>

    <!-- Section 1: Change Password Card -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-surface-container-high/80 shadow-xs space-y-5">
        <div class="flex items-center gap-3 pb-4 border-b border-surface-container-high/60">
            <div class="w-10 h-10 rounded-2xl bg-primary-fixed text-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">lock_reset</span>
            </div>
            <div>
                <h3 class="font-headline font-bold text-base text-on-surface">Cambiar Contraseña</h3>
                <p class="text-xs text-on-surface-variant">Asegúrate de utilizar una combinación única de al menos 8 caracteres con mayúsculas, minúsculas y números.</p>
            </div>
        </div>

        <form action="{{ route('profile.security.password') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">
                    Contraseña Actual <span class="text-error">*</span>
                </label>
                <input type="password" 
                       name="current_password" 
                       required 
                       placeholder="••••••••" 
                       class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high focus:ring-2 focus:ring-primary/20">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">
                        Nueva Contraseña <span class="text-error">*</span>
                    </label>
                    <input type="password" 
                           name="password" 
                           x-model="newPass"
                           required 
                           placeholder="••••••••" 
                           class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high focus:ring-2 focus:ring-primary/20">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">
                        Confirmar Nueva Contraseña <span class="text-error">*</span>
                    </label>
                    <input type="password" 
                           name="password_confirmation" 
                           x-model="confirmPass"
                           required 
                           placeholder="••••••••" 
                           class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high focus:ring-2 focus:ring-primary/20">
                </div>
            </div>

            <!-- Password Requirements Live Checklist -->
            <div class="p-3.5 bg-surface-container-low rounded-2xl border border-surface-container-high/60 grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px]">
                <div class="flex items-center gap-1.5" :class="hasMinLength ? 'text-emerald-700 font-bold' : 'text-outline'">
                    <span class="material-symbols-outlined text-sm" x-text="hasMinLength ? 'check_circle' : 'radio_button_unchecked'"></span>
                    <span>Mín. 8 caracteres</span>
                </div>
                <div class="flex items-center gap-1.5" :class="hasUpper ? 'text-emerald-700 font-bold' : 'text-outline'">
                    <span class="material-symbols-outlined text-sm" x-text="hasUpper ? 'check_circle' : 'radio_button_unchecked'"></span>
                    <span>Una mayúscula</span>
                </div>
                <div class="flex items-center gap-1.5" :class="hasLower ? 'text-emerald-700 font-bold' : 'text-outline'">
                    <span class="material-symbols-outlined text-sm" x-text="hasLower ? 'check_circle' : 'radio_button_unchecked'"></span>
                    <span>Una minúscula</span>
                </div>
                <div class="flex items-center gap-1.5" :class="hasNumber ? 'text-emerald-700 font-bold' : 'text-outline'">
                    <span class="material-symbols-outlined text-sm" x-text="hasNumber ? 'check_circle' : 'radio_button_unchecked'"></span>
                    <span>Al menos 1 número</span>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="px-6 py-2.5 bg-primary text-white font-headline font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container active:scale-95 transition-all">
                    Actualizar Contraseña
                </button>
            </div>
        </form>
    </div>

    <!-- Section 2: 2FA Status Card -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-surface-container-high/80 shadow-xs space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-surface-container-high/60">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white {{ $user->two_factor_enabled ? 'bg-emerald-600' : 'bg-surface-container-high text-outline' }}">
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">
                        {{ $user->two_factor_enabled ? 'verified_user' : 'shield' }}
                    </span>
                </div>
                <div>
                    <h3 class="font-headline font-bold text-base text-on-surface">
                        Autenticación en Dos Pasos (2FA): {{ $user->two_factor_enabled ? 'Activa' : 'Desactivada' }}
                    </h3>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        @if($user->two_factor_enabled)
                        Método configurado: <b>{{ $user->two_factor_type === 'totp' ? 'App Autenticadora (QR / TOTP)' : 'Correo Institucional (Email OTP)' }}</b>
                        @else
                        Añade una capa adicional de protección mediante código temporal o app autenticadora.
                        @endif
                    </p>
                </div>
            </div>

            @if($user->two_factor_enabled)
            <!-- Disable 2FA Form -->
            <form action="{{ route('profile.security.disable') }}" method="POST" onsubmit="return confirm('¿Estás seguro de desactivar la protección 2FA?');">
                @csrf
                <div class="flex items-center gap-2">
                    <input type="password" name="current_password" required placeholder="Contraseña actual" class="bg-surface-container-low text-xs rounded-xl p-2 border border-surface-container-high w-36">
                    <button type="submit" class="px-3 py-2 bg-error-container text-on-error-container hover:bg-error-container/80 text-xs font-bold rounded-xl transition-colors">
                        Desactivar 2FA
                    </button>
                </div>
            </form>
            @endif
        </div>

        @if($user->two_factor_enabled && !empty($user->two_factor_recovery_codes))
        <!-- Recovery Codes Box -->
        <div class="p-5 rounded-2xl bg-surface-container-low border border-surface-container-high/80 space-y-3" x-data="{ copiedCodes: false }">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold font-mono-tech uppercase text-primary flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">vpn_key</span>
                    <span>Códigos de Recuperación de Emergencia</span>
                </span>
                <button type="button" 
                        @click="navigator.clipboard.writeText('{{ implode("\n", $user->two_factor_recovery_codes) }}'); copiedCodes = true; setTimeout(() => copiedCodes = false, 3000)"
                        class="text-[11px] font-bold text-primary hover:underline flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs" x-text="copiedCodes ? 'done' : 'content_copy'"></span>
                    <span x-text="copiedCodes ? '¡Copiados!' : 'Copiar Códigos'"></span>
                </button>
            </div>
            <p class="text-xs text-on-surface-variant leading-relaxed">
                Si no tienes acceso a tu aplicación o correo temporalmente, puedes usar cualquiera de estos códigos de un solo uso para ingresar.
            </p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 font-mono-tech text-xs">
                @foreach($user->two_factor_recovery_codes as $code)
                <div class="p-2 bg-surface-container-lowest rounded-lg text-center font-bold tracking-wider text-on-surface border border-surface-container-high">
                    {{ $code }}
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if(!$user->two_factor_enabled)
        <!-- Setup 2FA Section -->
        <div class="space-y-6">
            <h3 class="font-headline font-bold text-base text-on-surface">Selecciona tu método de verificación preferido</h3>

            <!-- Method Selector Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button type="button" 
                        @click="method = 'totp'"
                        :class="method === 'totp' ? 'border-primary bg-primary-fixed/20' : 'border-surface-container-high bg-surface-container-lowest'"
                        class="p-4 rounded-2xl border-2 text-left transition-all flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl">qr_code_scanner</span>
                    </div>
                    <div>
                        <p class="font-bold text-xs text-on-surface">App Autenticadora (Recomendado)</p>
                        <p class="text-[11px] text-on-surface-variant mt-0.5 leading-relaxed">Google Authenticator, Microsoft Authenticator o Authy.</p>
                    </div>
                </button>

                <button type="button" 
                        @click="method = 'email'"
                        :class="method === 'email' ? 'border-primary bg-primary-fixed/20' : 'border-surface-container-high bg-surface-container-lowest'"
                        class="p-4 rounded-2xl border-2 text-left transition-all flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl">mark_email_read</span>
                    </div>
                    <div>
                        <p class="font-bold text-xs text-on-surface">Código por Correo Institucional</p>
                        <p class="text-[11px] text-on-surface-variant mt-0.5 leading-relaxed">Código OTP de 6 dígitos enviado a tu correo.</p>
                    </div>
                </button>
            </div>

            <!-- Option 1: TOTP QR Setup Box -->
            <div x-show="method === 'totp'" class="p-6 rounded-2xl bg-surface-container-low border border-surface-container-high/80 space-y-5">
                <div>
                    <h4 class="font-bold text-xs uppercase font-mono-tech text-primary">Paso 1: Escanea el Código QR</h4>
                    <p class="text-xs text-on-surface-variant mt-1">Abre tu app de autenticación y escanea el código o escribe la clave secreta manualmente.</p>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <div class="p-3 bg-white rounded-2xl shadow-xs border border-surface-container-high">
                        <img src="{{ $qrCodeUrl }}" alt="QR Code 2FA" class="w-40 h-40">
                    </div>

                    <div class="space-y-3 flex-1">
                        <div>
                            <span class="text-[11px] font-mono-tech text-outline uppercase font-bold">Clave Secreta Manual</span>
                            <div class="p-2.5 bg-surface-container-lowest rounded-xl font-mono-tech text-sm font-bold text-primary select-all border border-surface-container-high mt-1">
                                {{ $secret }}
                            </div>
                        </div>

                        <form action="{{ route('profile.security.totp') }}" method="POST" class="space-y-3 pt-2">
                            @csrf
                            <input type="hidden" name="secret" value="{{ $secret }}">
                            <div>
                                <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">
                                    Paso 2: Introduce el código de 6 dígitos generado por la app
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="text" name="code" required maxlength="6" placeholder="123456" class="w-36 bg-surface-container-lowest font-mono-tech font-bold text-center text-base rounded-xl p-2.5 border border-surface-container-high">
                                    <button type="submit" class="px-5 py-2.5 bg-primary text-white font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container">
                                        Activar 2FA
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Option 2: Email OTP Setup Box -->
            <div x-show="method === 'email'" style="display: none;" class="p-6 rounded-2xl bg-surface-container-low border border-surface-container-high/80 space-y-5">
                <div>
                    <h4 class="font-bold text-xs uppercase font-mono-tech text-primary">Verificación por Correo Electrónico</h4>
                    <p class="text-xs text-on-surface-variant mt-1">Enviaremos un código de seguridad de 6 dígitos a <b>{{ $user->email }}</b> para verificar la recepción.</p>
                </div>

                @if(!session('email_2fa_requested'))
                <form action="{{ route('profile.security.email.request') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 bg-primary text-white font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">send</span>
                        <span>Enviar Código de Prueba</span>
                    </button>
                </form>
                @else
                <form action="{{ route('profile.security.email.confirm') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">
                            Introduce el código de 6 dígitos recibido por correo
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="text" name="code" required maxlength="6" placeholder="123456" class="w-36 bg-surface-container-lowest font-mono-tech font-bold text-center text-base rounded-xl p-2.5 border border-surface-container-high">
                            <button type="submit" class="px-5 py-2.5 bg-primary text-white font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container">
                                Confirmar y Activar 2FA
                            </button>
                        </div>
                    </div>
                </form>
                @endif
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
