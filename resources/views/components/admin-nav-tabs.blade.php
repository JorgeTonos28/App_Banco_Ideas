<div class="flex items-center gap-2 border-b border-surface-container-high overflow-x-auto no-scrollbar pb-px pt-1">
    <!-- Tab 1: Panel de Innovación (Dashboard) -->
    <a href="{{ route('admin.dashboard') }}" 
       class="px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 whitespace-nowrap transition-all flex items-center gap-2 {{ request()->routeIs('admin.dashboard') ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-surface-container-high' }}">
        <span class="material-symbols-outlined text-lg" style="{{ request()->routeIs('admin.dashboard') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">analytics</span>
        <span>Panel de Innovación</span>
    </a>

    <!-- Tab 2: Gestión de Ideas -->
    <a href="{{ route('admin.ideas.index') }}" 
       class="px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 whitespace-nowrap transition-all flex items-center gap-2 {{ request()->routeIs('admin.ideas.*') ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-surface-container-high' }}">
        <span class="material-symbols-outlined text-lg" style="{{ request()->routeIs('admin.ideas.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">tune</span>
        <span>Gestión de Ideas</span>
    </a>

    <!-- Tab 3: Usuarios -->
    <a href="{{ route('admin.users.index') }}" 
       class="px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 whitespace-nowrap transition-all flex items-center gap-2 {{ request()->routeIs('admin.users.*') ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-surface-container-high' }}">
        <span class="material-symbols-outlined text-lg" style="{{ request()->routeIs('admin.users.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">group</span>
        <span>Usuarios</span>
    </a>

    <!-- Tab 4: Estructura organizacional -->
    <a href="{{ route('admin.regionals.index') }}" 
       class="px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 whitespace-nowrap transition-all flex items-center gap-2 {{ request()->routeIs('admin.regionals.*') ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-surface-container-high' }}">
        <span class="material-symbols-outlined text-lg" style="{{ request()->routeIs('admin.regionals.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">location_city</span>
        <span>Estructura</span>
    </a>

    <!-- Tab 5: Categorías -->
    <a href="{{ route('admin.categories.index') }}" 
       class="px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 whitespace-nowrap transition-all flex items-center gap-2 {{ request()->routeIs('admin.categories.*') ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-surface-container-high' }}">
        <span class="material-symbols-outlined text-lg" style="{{ request()->routeIs('admin.categories.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">category</span>
        <span>Categorías</span>
    </a>

    <!-- Tab 6: Etiquetas -->
    <a href="{{ route('admin.tags.index') }}" 
       class="px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 whitespace-nowrap transition-all flex items-center gap-2 {{ request()->routeIs('admin.tags.*') ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-surface-container-high' }}">
        <span class="material-symbols-outlined text-lg" style="{{ request()->routeIs('admin.tags.*') ? 'font-variation-settings: \'FILL\' 1;' : '' }}">sell</span>
        <span>Etiquetas</span>
    </a>

    <a href="{{ route('admin.ai.index') }}"
       class="px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 whitespace-nowrap transition-all flex items-center gap-2 {{ request()->routeIs('admin.ai.*') ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-surface-container-high' }}">
        <span class="material-symbols-outlined text-lg">psychology</span>
        <span>Inteligencia artificial</span>
    </a>
</div>
