<!DOCTYPE html>
<html lang="es" class="h-full bg-surface">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Iniciar Sesión - INNOVATEP Ideas</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-on-surface flex flex-col justify-center">

    <div class="flex flex-col lg:flex-row min-h-screen w-full overflow-hidden relative">
        <!-- Background Ambient Glow -->
        <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-bl from-primary-fixed/20 to-transparent pointer-events-none z-0"></div>
        <div class="absolute bottom-0 left-1/4 w-96 h-96 bg-secondary-fixed/10 blur-[120px] rounded-full pointer-events-none z-0"></div>

        <!-- Left Side: Brand & Innovation Visuals -->
        <div class="w-full lg:w-1/2 relative flex flex-col justify-between p-8 lg:p-16 z-10 bg-gradient-to-b from-surface to-surface-container-low/50 border-b lg:border-b-0 lg:border-r border-surface-container-high/60">
            <!-- Animated SVG Nodes -->
            <div class="absolute inset-0 w-full h-full opacity-30 pointer-events-none z-0">
                <svg class="w-full h-full" preserveAspectRatio="none" viewBox="0 0 100 100">
                    <circle class="fill-primary animate-pulse" cx="20" cy="30" r="1.5"></circle>
                    <circle class="fill-secondary-container animate-ping" cx="80" cy="40" r="2"></circle>
                    <circle class="fill-tertiary" cx="50" cy="70" r="2"></circle>
                    <circle class="fill-primary" cx="30" cy="80" r="1"></circle>
                    <path class="text-primary/30" d="M20,30 Q50,20 80,40 T50,70 Q40,50 20,30" fill="none" stroke="currentColor" stroke-width="0.3"></path>
                    <path class="text-secondary/30" d="M50,70 L30,80 L20,30" fill="none" stroke="currentColor" stroke-width="0.2"></path>
                </svg>
            </div>

            <!-- Brand Header -->
            <div class="z-10">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-primary-fixed rounded-full mb-6 shadow-xs">
                    <span class="material-symbols-outlined text-primary text-sm" style="font-variation-settings: 'FILL' 1;">lightbulb</span>
                    <span class="font-mono-tech text-xs text-on-primary-fixed uppercase tracking-wider font-semibold">Banco de Innovación</span>
                </div>

                <h1 class="font-headline font-extrabold text-4xl lg:text-5xl text-primary mb-4 leading-tight">
                    INNOVATEP <br>
                    <span class="text-on-surface">Ideas</span>
                </h1>

                <p class="text-base lg:text-lg text-on-surface-variant max-w-md leading-relaxed">
                    Un espacio colaborativo para transformar ideas en oportunidades y proyectos reales para INFOTEP.
                </p>
            </div>

            <!-- Central Visual Illustration -->
            <div class="z-10 my-8 flex items-center justify-center relative max-w-sm mx-auto">
                <div class="w-full aspect-video rounded-2xl bg-gradient-to-tr from-primary-container via-primary to-secondary-container p-1 shadow-2xl relative overflow-hidden group">
                    <div class="w-full h-full bg-surface-container-lowest/90 backdrop-blur-md rounded-xl p-6 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="font-mono-tech text-xs text-primary font-bold">#ComunidadInnovadora</span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                        </div>
                        <p class="text-sm font-medium text-on-surface italic">
                            “Aquí cualquier persona puede tener una gran idea que transforme nuestra institución.”
                        </p>
                        <div class="flex items-center gap-2 text-xs text-on-surface-variant">
                            <span class="material-symbols-outlined text-base text-secondary">verified</span>
                            <span>Iniciativa Oficial INFOTEP</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Institutional Footer -->
            <div class="z-10 text-xs text-on-surface-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-sm text-outline">account_balance</span>
                <span>Instituto Nacional de Formación Técnico Profesional (INFOTEP)</span>
            </div>
        </div>

        <!-- Right Side: Authentication Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 lg:p-16 z-10">
            <div class="w-full max-w-md bg-surface-container-lowest rounded-3xl p-8 sm:p-10 shadow-xl border border-surface-container-high/80 relative">
                <div class="mb-8 text-center">
                    <h2 class="font-headline font-bold text-2xl text-on-surface mb-2">Acceder a tu cuenta</h2>
                    <p class="text-xs sm:text-sm text-on-surface-variant">Ingresa tus credenciales institucionales</p>
                </div>

                @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-error-container/60 border border-error/30 text-error text-xs font-medium flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-lg">error</span>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <form action="{{ route('login') }}" method="POST" class="space-y-5" x-data="{ showPass: false }">
                    @csrf

                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Correo Institucional</label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined absolute left-3.5 text-outline text-lg">alternate_email</span>
                            <input id="email" 
                                   type="email" 
                                   name="email" 
                                   value="{{ old('email', 'admin@infotep.gob.do') }}" 
                                   required 
                                   autofocus 
                                   placeholder="usuario@infotep.gob.do"
                                   class="w-full bg-surface-container-low text-on-surface text-sm rounded-xl py-3 pl-11 pr-4 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-semibold text-on-surface-variant">Contraseña</label>
                        </div>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined absolute left-3.5 text-outline text-lg">lock</span>
                            <input id="password" 
                                   :type="showPass ? 'text' : 'password'" 
                                   name="password" 
                                   value="password123"
                                   required 
                                   placeholder="••••••••"
                                   class="w-full bg-surface-container-low text-on-surface text-sm rounded-xl py-3 pl-11 pr-11 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                            <button type="button" 
                                    @click="showPass = !showPass" 
                                    class="absolute right-3 text-outline hover:text-on-surface focus:outline-none transition-colors p-1">
                                <span class="material-symbols-outlined text-lg" x-text="showPass ? 'visibility_off' : 'visibility'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 cursor-pointer select-none text-on-surface-variant hover:text-on-surface">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-primary focus:ring-primary border-outline-variant">
                            <span>Recordarme</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-primary to-primary-container text-white font-headline font-bold text-sm py-3.5 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center justify-center gap-2">
                        <span>Ingresar a INNOVATEP</span>
                        <span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </button>
                </form>

                <!-- Demo Access Helpers Box -->
                <div class="mt-8 pt-6 border-t border-surface-container-high/60 bg-surface-container-low/50 -mx-4 -mb-4 p-4 rounded-2xl">
                    <p class="text-[11px] font-mono-tech text-outline uppercase font-bold tracking-wider mb-2 text-center">Cuentas de Demostración</p>
                    <div class="space-y-1.5 text-xs text-on-surface-variant">
                        <div class="flex items-center justify-between p-1.5 rounded-lg bg-surface-container-lowest">
                            <span><b>Admin:</b> admin@infotep.gob.do</span>
                            <span class="font-mono-tech text-[10px] text-primary">pass: password123</span>
                        </div>
                        <div class="flex items-center justify-between p-1.5 rounded-lg bg-surface-container-lowest">
                            <span><b>Docente:</b> maria.gonzalez@infotep.gob.do</span>
                            <span class="font-mono-tech text-[10px] text-primary">pass: password123</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
