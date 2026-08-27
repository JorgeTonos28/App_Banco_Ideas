<aside class="hidden md:flex fixed left-0 top-0 h-full w-72 bg-surface-container-lowest z-40 flex-col border-r border-surface-container-high/80 shadow-[4px_0_24px_rgba(0,62,111,0.02)]">
    <!-- Brand Header -->
    <div class="p-6 flex items-center gap-3 border-b border-surface-container-high/60">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-white shadow-md">
            <span class="material-symbols-outlined text-2xl">lightbulb</span>
        </div>
        <div>
            <span class="font-headline text-xl font-bold tracking-tight text-primary block leading-none">INNOVATEP</span>
            <span class="text-[11px] font-mono-tech uppercase text-outline tracking-wider font-semibold">Centro de Innovación</span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 space-y-1.5 overflow-y-auto py-5 no-scrollbar">
        <!-- 1. Mis Ideas (Primer Módulo / Módulo Inicial) -->
        <a href="{{ route('my-ideas.index') }}" 
           class="flex items-center px-4 py-3 rounded-xl transition-all gap-3.5 text-sm font-medium {{ request()->routeIs('my-ideas.*') || request()->is('/') ? 'bg-primary text-white shadow-sm font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary' }}">
            <span class="material-symbols-outlined text-xl" style="{{ request()->routeIs('my-ideas.*') || request()->is('/') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">folder_special</span>
            <span>Banco de Ideas</span>
        </a>

        <a href="{{ route('tasks.index') }}"
           class="flex items-center px-4 py-3 rounded-xl transition-all gap-3.5 text-sm font-medium {{ request()->routeIs('tasks.*') ? 'bg-primary text-white shadow-sm font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary' }}">
            <span class="material-symbols-outlined text-xl" style="{{ request()->routeIs('tasks.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">checklist</span>
            <span>Tareas</span>
        </a>

        <!-- 2. Comunidad (Segundo Módulo / Feed de Innovación) -->
        <a href="{{ route('community') }}" 
           class="flex items-center px-4 py-3 rounded-xl transition-all gap-3.5 text-sm font-medium {{ request()->routeIs('community') ? 'bg-primary text-white shadow-sm font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary' }}">
            <span class="material-symbols-outlined text-xl" style="{{ request()->routeIs('community') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">hub</span>
            <span>Comunidad</span>
        </a>

        <!-- 3. Explorar Ideas -->
        <a href="{{ route('ideas.index') }}" 
           class="flex items-center px-4 py-3 rounded-xl transition-all gap-3.5 text-sm font-medium {{ request()->routeIs('ideas.index') || request()->routeIs('ideas.show') ? 'bg-primary text-white shadow-sm font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary' }}">
            <span class="material-symbols-outlined text-xl" style="{{ request()->routeIs('ideas.index') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">explore</span>
            <span>Explorar Ideas</span>
        </a>

        <!-- 4. Ranking -->
        <a href="{{ route('ranking.index') }}" 
           class="flex items-center px-4 py-3 rounded-xl transition-all gap-3.5 text-sm font-medium {{ request()->routeIs('ranking.*') ? 'bg-primary text-white shadow-sm font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary' }}">
            <span class="material-symbols-outlined text-xl" style="{{ request()->routeIs('ranking.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">leaderboard</span>
            <span>Ranking</span>
        </a>

        <!-- 5. Administración (Exclusivo Administradores) -->
        @auth
        @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.dashboard') }}" 
           class="flex items-center px-4 py-3 rounded-xl transition-all gap-3.5 text-sm font-medium {{ request()->routeIs('admin.*') ? 'bg-primary text-white shadow-sm font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary' }}">
            <span class="material-symbols-outlined text-xl" style="{{ request()->routeIs('admin.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">admin_panel_settings</span>
            <span>Administración</span>
        </a>
        @endif
        @endauth

        <!-- CTA: Nueva Idea -->
        <div class="pt-3 pb-2">
            <a href="{{ route('ideas.create') }}" 
               class="flex items-center justify-center px-4 py-3 rounded-xl bg-gradient-to-r from-primary-container to-primary text-white shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all gap-2 text-sm font-semibold group">
                <span class="material-symbols-outlined text-xl group-hover:rotate-90 transition-transform">add_circle</span>
                <span>Nueva Idea</span>
            </a>
        </div>
    </nav>

    <!-- User Profile & Footer Area -->
    <div class="mt-auto p-4 border-t border-surface-container-high bg-surface-container-lowest/80">
        @auth
        <div class="flex items-center gap-3 p-2 rounded-xl bg-surface-container-low/70 border border-surface-container-high/50">
            <img src="{{ auth()->user()->avatar_url }}" 
                 alt="{{ auth()->user()->name }}" 
                 class="w-10 h-10 rounded-full object-cover shadow-sm ring-2 ring-white">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-on-surface truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-on-surface-variant truncate font-mono-tech">
                    {{ auth()->user()->isAdmin() ? 'Administrador' : (auth()->user()->job_title ?: 'Colaborador') }}
                </p>
            </div>
            <a href="{{ route('profile.show') }}" title="Ver Perfil" class="text-on-surface-variant hover:text-primary transition-colors p-1">
                <span class="material-symbols-outlined text-lg">person</span>
            </a>
        </div>

        <div class="flex items-center justify-between mt-3 pt-2 px-2 text-xs">
            <a href="{{ route('notifications.index') }}" class="flex items-center gap-1.5 text-on-surface-variant hover:text-primary transition-colors relative">
                <span class="material-symbols-outlined text-lg">notifications</span>
                <span>Avisos</span>
                @if(auth()->user()->unreadNotifications->count() > 0)
                <span class="w-2 h-2 rounded-full bg-secondary-container animate-pulse"></span>
                @endif
            </a>

            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="flex items-center gap-1 text-error hover:text-error-container hover:bg-error/10 px-2 py-1 rounded-md transition-colors font-medium">
                    <span class="material-symbols-outlined text-base">logout</span>
                    <span>Salir</span>
                </button>
            </form>
        </div>
        @else
        <a href="{{ route('login') }}" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-primary text-white rounded-xl font-medium text-sm shadow-sm hover:bg-primary-container transition-colors">
            <span class="material-symbols-outlined text-lg">login</span>
            <span>Iniciar Sesión</span>
        </a>
        @endauth
    </div>
</aside>
