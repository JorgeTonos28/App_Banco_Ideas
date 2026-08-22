<!DOCTYPE html>
<html lang="es" class="h-full bg-surface">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Activar Cuenta - Onboarding INNOVATEP</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-on-surface flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">

    <div class="max-w-xl w-full mx-auto space-y-8">
        <!-- Brand Header -->
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-primary to-primary-container text-white shadow-lg mb-2">
                <span class="material-symbols-outlined text-3xl">lightbulb</span>
            </div>
            <h1 class="font-headline font-extrabold text-3xl text-on-surface">¡Bienvenido a INNOVATEP Ideas!</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant max-w-md mx-auto">
                Has sido invitado a formar parte del Banco Institucional de Innovación de INFOTEP. Configura tu acceso para comenzar a proponer y colaborar.
            </p>
        </div>

        @if($errors->any())
        <div class="p-4 rounded-2xl bg-error-container/60 border border-error/30 text-error text-xs font-medium space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">error</span>
                <span>Por favor revisa los errores:</span>
            </div>
            <ul class="list-disc list-inside pl-2 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Onboarding Activation Card -->
        <div class="bg-surface-container-lowest rounded-3xl p-8 sm:p-10 shadow-xl border border-surface-container-high/80">
            <form action="{{ route('onboarding.activate', $invitation->token) }}" method="POST" class="space-y-5">
                @csrf

                <!-- Name & Email (Preloaded) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1.5">Nombre Completo <span class="text-error">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $invitation->name) }}" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1.5">Correo Institucional</label>
                        <input type="email" value="{{ $invitation->email }}" disabled class="w-full bg-surface-container text-xs rounded-xl p-3 border border-surface-container-high text-outline cursor-not-allowed font-mono-tech">
                    </div>
                </div>

                <!-- Regional, Job Title & Department -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1.5">Dirección Regional <span class="text-error">*</span></label>
                        <select name="regional_id" required class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                            <option value="">Seleccionar Regional</option>
                            @foreach($regionals as $r)
                            <option value="{{ $r->id }}" {{ (old('regional_id', $invitation->regional_id) == $r->id) ? 'selected' : '' }}>
                                {{ $r->code }} - {{ $r->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1.5">Cargo / Función</label>
                        <input type="text" name="job_title" value="{{ old('job_title', $invitation->job_title) }}" placeholder="Ej.: Docente Técnico" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1.5">Departamento o Taller</label>
                    <input type="text" name="department" value="{{ old('department', $invitation->department) }}" placeholder="Ej.: Dirección de Tecnología" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high">
                </div>

                <!-- Password Setup -->
                <div class="pt-3 border-t border-surface-container-high/60 space-y-4">
                    <p class="text-xs font-bold text-on-surface uppercase font-mono-tech">Establece tu Contraseña Segura</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-on-surface-variant mb-1">Contraseña (Mín. 8 caracteres) <span class="text-error">*</span></label>
                            <input type="password" name="password" required placeholder="••••••••" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high focus:ring-2 focus:ring-primary/20">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-on-surface-variant mb-1">Confirmar Contraseña <span class="text-error">*</span></label>
                            <input type="password" name="password_confirmation" required placeholder="••••••••" class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high focus:ring-2 focus:ring-primary/20">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1.5">Breve Biografía / Intereses de Innovación</label>
                    <textarea name="bio" rows="2" placeholder="Cuéntanos sobre tus áreas de experiencia técnica..." class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high resize-none">{{ old('bio') }}</textarea>
                </div>

                <div class="pt-4 border-t border-surface-container-high">
                    <button type="submit" class="w-full bg-gradient-to-r from-primary to-primary-container text-white font-headline font-bold text-sm py-3.5 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                        <span>Activar Mi Cuenta y Entrar</span>
                        <span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </button>
                </div>
            </form>
        </div>

        <p class="text-center text-xs text-outline">
            Instituto Nacional de Formación Técnico Profesional (INFOTEP) • República Dominicana
        </p>
    </div>

</body>
</html>
