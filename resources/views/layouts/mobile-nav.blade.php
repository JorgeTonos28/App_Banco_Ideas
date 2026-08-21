<div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-surface-container-lowest/95 backdrop-blur-xl border-t border-surface-container-high px-4 py-2 flex items-center justify-around shadow-[0_-4px_20px_rgba(0,0,0,0.04)]">
    <!-- Home -->
    <a href="{{ route('home') }}" class="flex flex-col items-center gap-1 py-1 px-2 text-xs font-medium {{ request()->routeIs('home') ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' }}">
        <span class="material-symbols-outlined text-22px" style="{{ request()->routeIs('home') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">home</span>
        <span>Inicio</span>
    </a>

    <!-- Ideas -->
    <a href="{{ route('ideas.index') }}" class="flex flex-col items-center gap-1 py-1 px-2 text-xs font-medium {{ request()->routeIs('ideas.index') ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' }}">
        <span class="material-symbols-outlined text-22px" style="{{ request()->routeIs('ideas.index') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">explore</span>
        <span>Ideas</span>
    </a>

    <!-- Center Floating Action: New Idea -->
    <div class="-mt-6">
        <a href="{{ route('ideas.create') }}" class="w-12 h-12 rounded-full bg-gradient-to-tr from-primary to-primary-container text-white flex items-center justify-center shadow-lg active:scale-95 transition-transform">
            <span class="material-symbols-outlined text-26px">add</span>
        </a>
    </div>

    <!-- Ranking -->
    <a href="{{ route('ranking.index') }}" class="flex flex-col items-center gap-1 py-1 px-2 text-xs font-medium {{ request()->routeIs('ranking.*') ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' }}">
        <span class="material-symbols-outlined text-22px" style="{{ request()->routeIs('ranking.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">leaderboard</span>
        <span>Ranking</span>
    </a>

    <!-- Profile / Mis ideas -->
    @auth
    <a href="{{ route('my-ideas.index') }}" class="flex flex-col items-center gap-1 py-1 px-2 text-xs font-medium {{ request()->routeIs('my-ideas.*') || request()->routeIs('profile.*') ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' }}">
        <span class="material-symbols-outlined text-22px" style="{{ request()->routeIs('my-ideas.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">person</span>
        <span>Perfil</span>
    </a>
    @else
    <a href="{{ route('login') }}" class="flex flex-col items-center gap-1 py-1 px-2 text-xs font-medium text-on-surface-variant hover:text-primary">
        <span class="material-symbols-outlined text-22px">login</span>
        <span>Entrar</span>
    </a>
    @endauth
</div>
