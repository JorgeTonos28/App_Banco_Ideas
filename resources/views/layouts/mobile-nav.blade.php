<div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-surface-container-lowest/95 backdrop-blur-xl border-t border-surface-container-high px-3 py-2 flex items-center justify-around shadow-[0_-4px_20px_rgba(0,0,0,0.04)]">
    <!-- 1. Mis Ideas -->
    <a href="{{ route('my-ideas.index') }}" class="flex flex-col items-center gap-1 py-1 px-1.5 text-xs font-medium {{ request()->routeIs('my-ideas.*') || request()->is('/') ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' }}">
        <span class="material-symbols-outlined text-[22px]" style="{{ request()->routeIs('my-ideas.*') || request()->is('/') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">folder_special</span>
        <span class="text-[10px]">Ideas</span>
    </a>

    <a href="{{ route('tasks.index') }}" class="flex flex-col items-center gap-1 py-1 px-1.5 text-xs font-medium {{ request()->routeIs('tasks.*') ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' }}">
        <span class="material-symbols-outlined text-[22px]" style="{{ request()->routeIs('tasks.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">checklist</span>
        <span class="text-[10px]">Tareas</span>
    </a>

    <!-- 2. Comunidad -->
    <a href="{{ route('community') }}" class="flex flex-col items-center gap-1 py-1 px-1.5 text-xs font-medium {{ request()->routeIs('community') ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' }}">
        <span class="material-symbols-outlined text-[22px]" style="{{ request()->routeIs('community') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">hub</span>
        <span class="text-[10px]">Comunidad</span>
    </a>

    <!-- Center Floating Action: New Idea -->
    <div class="-mt-6">
        <a href="{{ route('ideas.create') }}" class="w-12 h-12 rounded-full bg-gradient-to-tr from-primary to-primary-container text-white flex items-center justify-center shadow-lg active:scale-95 transition-transform">
            <span class="material-symbols-outlined text-[26px]">add</span>
        </a>
    </div>

    @auth
        @if(auth()->user()->isAdmin())
        <!-- Admin Dashboard Button for Admins -->
        <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center gap-1 py-1 px-1.5 text-xs font-medium {{ request()->routeIs('admin.*') ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' }}">
            <span class="material-symbols-outlined text-[22px]" style="{{ request()->routeIs('admin.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">admin_panel_settings</span>
            <span class="text-[10px]">Admin</span>
        </a>
        @else
        <!-- Ranking for standard users -->
        <a href="{{ route('ranking.index') }}" class="flex flex-col items-center gap-1 py-1 px-1.5 text-xs font-medium {{ request()->routeIs('ranking.*') ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' }}">
            <span class="material-symbols-outlined text-[22px]" style="{{ request()->routeIs('ranking.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">leaderboard</span>
            <span class="text-[10px]">Ranking</span>
        </a>
        @endif

        <!-- Profile -->
        <a href="{{ route('profile.show') }}" class="flex flex-col items-center gap-1 py-1 px-1.5 text-xs font-medium {{ request()->routeIs('profile.*') ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' }}">
            <span class="material-symbols-outlined text-[22px]" style="{{ request()->routeIs('profile.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">person</span>
            <span class="text-[10px]">Perfil</span>
        </a>
    @else
        <a href="{{ route('ranking.index') }}" class="flex flex-col items-center gap-1 py-1 px-1.5 text-xs font-medium {{ request()->routeIs('ranking.*') ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' }}">
            <span class="material-symbols-outlined text-[22px]">leaderboard</span>
            <span class="text-[10px]">Ranking</span>
        </a>

        <a href="{{ route('login') }}" class="flex flex-col items-center gap-1 py-1 px-1.5 text-xs font-medium text-on-surface-variant hover:text-primary">
            <span class="material-symbols-outlined text-[22px]">login</span>
            <span class="text-[10px]">Entrar</span>
        </a>
    @endauth
</div>
