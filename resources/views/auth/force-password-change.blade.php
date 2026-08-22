<!DOCTYPE html>
<html lang="es" class="h-full bg-surface">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Cambio Obligatorio de Contraseña - INNOVATEP</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-on-surface flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">

    <div class="max-w-md w-full mx-auto space-y-6">
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-amber-500 text-white shadow-md mb-1">
                <span class="material-symbols-outlined text-2xl">lock_reset</span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl text-on-surface">Cambio Obligatorio de Contraseña</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant">
                Por motivos de seguridad institucional, debes reemplazar tu contraseña temporal por una contraseña privada y segura antes de ingresar.
            </p>
        </div>

        @if($errors->any())
        <div class="p-4 rounded-2xl bg-error-container/60 border border-error/30 text-error text-xs font-medium space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">error</span>
                <span>Por favor corrige los siguientes datos:</span>
            </div>
            <ul class="list-disc list-inside pl-2 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="bg-surface-container-lowest rounded-3xl p-8 shadow-xl border border-surface-container-high/80">
            <form action="{{ route('password.force-update') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Contraseña Temporal Actual <span class="text-error">*</span></label>
                    <input type="password" name="current_password" required placeholder="••••••••" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high focus:ring-2 focus:ring-primary/20">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Nueva Contraseña Segura <span class="text-error">*</span></label>
                    <input type="password" name="password" required placeholder="Mínimo 8 caracteres (letras y números)" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high focus:ring-2 focus:ring-primary/20">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Confirmar Nueva Contraseña <span class="text-error">*</span></label>
                    <input type="password" name="password_confirmation" required placeholder="Repite la nueva contraseña" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high focus:ring-2 focus:ring-primary/20">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-gradient-to-r from-primary to-primary-container text-white font-headline font-bold text-sm py-3.5 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                        <span>Actualizar Contraseña y Continuar</span>
                        <span class="material-symbols-outlined text-base">check_circle</span>
                    </button>
                </div>
            </form>

            <div class="mt-4 pt-4 border-t border-surface-container-high/60 text-center">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs text-error hover:underline font-medium">
                        Cancelar y cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
