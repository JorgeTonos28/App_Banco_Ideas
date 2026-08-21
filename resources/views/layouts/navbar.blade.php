<header class="sticky top-0 z-30 h-16 bg-surface/90 backdrop-blur-xl border-b border-surface-container-high/80 px-4 sm:px-6 lg:px-8 flex items-center justify-between">
    <!-- Left: Mobile Brand & Search Trigger -->
    <div class="flex items-center gap-4 flex-1 max-w-2xl">
        <!-- Mobile Logo -->
        <div class="flex md:hidden items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center text-white shadow-sm">
                <span class="material-symbols-outlined text-lg">lightbulb</span>
            </div>
            <span class="font-headline font-bold text-lg text-primary">INNOVATEP</span>
        </div>

        <!-- Global Search Input Button -->
        <button @click="searchOpen = true" 
                class="w-full max-w-lg flex items-center justify-between px-4 py-2 bg-surface-container-low/80 hover:bg-surface-container rounded-full text-sm text-on-surface-variant transition-all border border-surface-container-high shadow-xs group">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-lg text-outline group-hover:text-primary transition-colors">search</span>
                <span class="text-xs sm:text-sm font-normal">Buscar ideas, personas o etiquetas...</span>
            </div>
            <kbd class="hidden sm:inline-flex items-center gap-0.5 px-2 py-0.5 text-[10px] font-mono-tech bg-surface-container-lowest text-outline rounded border border-outline-variant/40 shadow-2xs">
                Ctrl K
            </kbd>
        </button>
    </div>

    <!-- Right: Quick Actions & Profile -->
    <div class="flex items-center gap-3">
        @auth
        <!-- Notifications Bell Button -->
        <a href="{{ route('notifications.index') }}" 
           class="relative w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container-low hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-2xl">notifications</span>
            @if(auth()->user()->unreadNotifications->count() > 0)
            <span class="absolute top-2 right-2 w-2.5 h-2.5 bg-secondary-container rounded-full ring-2 ring-surface"></span>
            @endif
        </a>

        <!-- User Quick Avatar Dropdown -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" 
                    @click.away="open = false" 
                    class="flex items-center gap-2 p-1 rounded-full hover:bg-surface-container-low transition-colors focus:outline-none">
                <img src="{{ auth()->user()->avatar_url }}" 
                     alt="{{ auth()->user()->name }}" 
                     class="w-8 h-8 rounded-full object-cover ring-2 ring-primary/20">
            </button>

            <!-- Dropdown Menu -->
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-100" 
                 x-transition:enter-start="transform opacity-0 scale-95" 
                 x-transition:enter-end="transform opacity-100 scale-100" 
                 x-transition:leave="transition ease-in duration-75" 
                 x-transition:leave-start="transform opacity-100 scale-100" 
                 x-transition:leave-end="transform opacity-0 scale-95" 
                 class="absolute right-0 mt-2 w-56 bg-surface-container-lowest rounded-2xl shadow-xl border border-surface-container-high py-2 z-50">
                <div class="px-4 py-2 border-b border-surface-container-high/60">
                    <p class="text-xs font-bold text-on-surface truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-on-surface-variant truncate font-mono-tech">{{ auth()->user()->email }}</p>
                </div>

                <a href="{{ route('profile.show') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-on-surface hover:bg-surface-container-low">
                    <span class="material-symbols-outlined text-base">person</span>
                    <span>Mi Perfil</span>
                </a>
                <a href="{{ route('my-ideas.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-on-surface hover:bg-surface-container-low">
                    <span class="material-symbols-outlined text-base">folder_special</span>
                    <span>Mis Ideas</span>
                </a>
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-on-surface hover:bg-surface-container-low">
                    <span class="material-symbols-outlined text-base">settings</span>
                    <span>Editar Perfil</span>
                </a>

                @if(auth()->user()->isAdmin())
                <div class="border-t border-surface-container-high/60 my-1"></div>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-primary hover:bg-surface-container-low">
                    <span class="material-symbols-outlined text-base">admin_panel_settings</span>
                    <span>Panel Administrativo</span>
                </a>
                @endif

                <div class="border-t border-surface-container-high/60 my-1"></div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs font-medium text-error hover:bg-error-container/20 text-left">
                        <span class="material-symbols-outlined text-base">logout</span>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </div>
        @else
        <a href="{{ route('login') }}" class="flex items-center gap-1.5 px-4 py-2 bg-primary text-white rounded-full text-xs font-semibold hover:bg-primary-container transition-colors shadow-xs">
            <span class="material-symbols-outlined text-base">login</span>
            <span>Acceder</span>
        </a>
        @endauth
    </div>
</header>
