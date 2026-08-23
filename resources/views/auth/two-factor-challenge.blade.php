<!DOCTYPE html>
<html lang="es" class="h-full bg-surface">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Verificación 2FA - INNOVATEP Ideas</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-on-surface flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8" x-data="{ useRecovery: false }">

    <div class="max-w-md w-full mx-auto space-y-6">
        <!-- Header -->
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary text-white shadow-lg mb-1">
                <span class="material-symbols-outlined text-3xl">verified_user</span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl text-on-surface">Verificación en Dos Pasos</h1>
            
            <template x-if="!useRecovery">
                <p class="text-xs sm:text-sm text-on-surface-variant">
                    @if($user->two_factor_type === 'email')
                    Se ha enviado un código de 6 dígitos a tu correo <b>{{ $user->email }}</b>.
                    @else
                    Abre tu aplicación autenticadora (Google Authenticator / Authy) e introduce el código temporal de 6 dígitos.
                    @endif
                </p>
            </template>
            <template x-if="useRecovery">
                <p class="text-xs sm:text-sm text-on-surface-variant">
                    Introduce uno de tus códigos de recuperación de emergencia generados al activar 2FA.
                </p>
            </template>
        </div>

        @if(session('error'))
        <div class="p-4 rounded-2xl bg-error-container/60 border border-error/30 text-error text-xs font-medium flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">error</span>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-medium flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        @if(session('demo_2fa_code'))
        <div class="p-3 bg-secondary-fixed/20 border border-secondary-fixed rounded-xl text-xs font-mono-tech text-center">
            <span class="text-outline">Código demo generado:</span> <b>{{ session('demo_2fa_code') }}</b>
        </div>
        @endif

        <div class="bg-surface-container-lowest rounded-3xl p-8 shadow-xl border border-surface-container-high/80">
            <form action="{{ route('2fa.verify') }}" method="POST" class="space-y-5">
                @csrf

                <div x-show="!useRecovery">
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-2 text-center">
                        Código de Seguridad (6 Dígitos)
                    </label>
                    <input type="text" 
                           name="code" 
                           maxlength="10"
                           required
                           :disabled="useRecovery"
                           autofocus 
                           autocomplete="one-time-code"
                           placeholder="123456" 
                           class="w-full bg-surface-container-low text-center text-2xl font-mono-tech font-bold tracking-widest text-primary rounded-2xl py-3 px-4 border border-surface-container-high focus:ring-2 focus:ring-primary/30 focus:border-primary">
                </div>

                <div x-show="useRecovery" style="display: none;">
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-2 text-center">
                        Código de Recuperación de Emergencia
                    </label>
                    <input type="text" 
                           name="code" 
                           required
                           :disabled="!useRecovery"
                           placeholder="ABCDE12345" 
                           class="w-full bg-surface-container-low text-center text-lg font-mono-tech font-bold uppercase rounded-2xl py-3 px-4 border border-surface-container-high focus:ring-2 focus:ring-primary/30 focus:border-primary">
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-primary to-primary-container text-white font-headline font-bold text-sm py-3.5 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                    <span>Verificar y Acceder</span>
                    <span class="material-symbols-outlined text-lg">arrow_forward</span>
                </button>
            </form>

            <div class="mt-6 pt-4 border-t border-surface-container-high/60 flex flex-col items-center gap-2.5 text-xs">
                @if($user->two_factor_type === 'email')
                <form action="{{ route('2fa.resend') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-primary hover:underline font-semibold flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">refresh</span>
                        <span>Reenviar código por correo</span>
                    </button>
                </form>
                @endif

                <button type="button" @click="useRecovery = !useRecovery" class="text-on-surface-variant hover:text-primary transition-colors">
                    <span x-text="useRecovery ? 'Volver a código de autenticación' : '¿No tienes acceso? Usar código de recuperación'"></span>
                </button>

                <a href="{{ route('login') }}" class="text-error hover:underline text-[11px] mt-1">
                    Cancelar e iniciar sesión con otra cuenta
                </a>
            </div>
        </div>
    </div>

</body>
</html>
