<div x-data="globalSearch()" 
     @keydown.window.prevent.ctrl.k="searchOpen = true; $nextTick(() => $refs.searchInput.focus())"
     @keydown.window.prevent.cmd.k="searchOpen = true; $nextTick(() => $refs.searchInput.focus())"
     @keydown.escape.window="searchOpen = false"
     x-show="searchOpen" 
     class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20" 
     style="display: none;">

    <!-- Backdrop -->
    <div x-show="searchOpen" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         @click="searchOpen = false" 
         class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs transition-opacity"></div>

    <!-- Search Dialog Card -->
    <div x-show="searchOpen" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100 scale-100" 
         x-transition:leave-end="opacity-0 scale-95" 
         class="relative mx-auto max-w-2xl transform divide-y divide-surface-container-high overflow-hidden rounded-2xl bg-surface-container-lowest shadow-2xl transition-all border border-surface-container-high">

        <!-- Search Input Bar -->
        <div class="relative flex items-center px-4 py-3">
            <span class="material-symbols-outlined text-2xl text-outline mr-3">search</span>
            <input x-ref="searchInput" 
                   x-model="query" 
                   @input.debounce.250ms="search()" 
                   type="text" 
                   class="h-10 w-full border-0 bg-transparent pr-4 text-sm text-on-surface placeholder:text-on-surface-variant/60 focus:ring-0 focus:outline-none" 
                   placeholder="Escribe para buscar ideas, colaboradores, categorías o etiquetas...">
            <button @click="searchOpen = false" class="text-outline hover:text-on-surface p-1 rounded-md">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Search Results Container -->
        <div class="max-h-96 scroll-py-3 overflow-y-auto p-4 space-y-4">
            <!-- Loading Indicator -->
            <div x-show="loading" class="text-center py-6 text-on-surface-variant text-sm flex items-center justify-center gap-2">
                <span class="material-symbols-outlined animate-spin text-primary">progress_activity</span>
                <span>Buscando en INNOVATEP...</span>
            </div>

            <!-- Empty / Initial State -->
            <div x-show="!loading && normalizedSearchQuery().length < 2" class="text-center py-8 text-on-surface-variant/80">
                <span class="material-symbols-outlined text-4xl text-outline mb-2">travel_explore</span>
                <p class="text-xs">Escribe al menos 2 letras para iniciar la búsqueda en toda la plataforma.</p>
            </div>

            <!-- No results -->
            <div x-show="!loading && normalizedSearchQuery().length >= 2 && results.ideas.length === 0 && results.people.length === 0 && results.categories.length === 0 && results.tags.length === 0"
                 class="text-center py-8 text-on-surface-variant">
                <span class="material-symbols-outlined text-4xl text-outline mb-2">sentiment_dissatisfied</span>
                <p class="text-sm font-semibold">No se encontraron resultados</p>
                <p class="text-xs mt-1">Intenta con otros términos o palabras clave.</p>
            </div>

            <!-- Group: Ideas -->
            <template x-if="results.ideas.length > 0">
                <div>
                    <h3 class="text-[11px] font-mono-tech uppercase font-bold text-outline tracking-wider mb-2">Ideas</h3>
                    <div class="space-y-1.5">
                        <template x-for="idea in results.ideas" :key="idea.id">
                            <a :href="idea.url" class="flex items-start justify-between p-2.5 rounded-xl hover:bg-surface-container-low transition-colors group">
                                <div class="flex items-start gap-2.5 min-w-0">
                                    <span class="material-symbols-outlined text-primary text-xl mt-0.5">lightbulb</span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors truncate" x-text="idea.title"></p>
                                        <p class="text-xs text-on-surface-variant line-clamp-1" x-text="idea.summary"></p>
                                    </div>
                                </div>
                                <div class="ml-2 flex shrink-0 flex-col items-end gap-1">
                                    <span class="rounded-full bg-primary-fixed px-2 py-0.5 text-[9px] font-bold text-primary" x-text="idea.context"></span>
                                    <span class="rounded-full bg-surface-container px-2 py-0.5 text-[10px] font-mono-tech text-on-surface-variant" x-text="idea.status"></span>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Group: People -->
            <template x-if="results.people.length > 0">
                <div>
                    <h3 class="text-[11px] font-mono-tech uppercase font-bold text-outline tracking-wider mb-2">Colaboradores</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <template x-for="person in results.people" :key="person.id">
                            <a :href="person.url" class="flex items-center gap-3 p-2 rounded-xl hover:bg-surface-container-low transition-colors">
                                <img :src="person.avatar" class="w-8 h-8 rounded-full object-cover">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-on-surface truncate" x-text="person.name"></p>
                                    <p class="text-[10px] text-on-surface-variant truncate" x-text="person.job_title || person.department"></p>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Group: Categories & Tags -->
            <template x-if="results.categories.length > 0 || results.tags.length > 0">
                <div>
                    <h3 class="text-[11px] font-mono-tech uppercase font-bold text-outline tracking-wider mb-2">Categorías y Etiquetas</h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="cat in results.categories" :key="cat.id">
                            <a :href="cat.url" class="inline-flex items-center gap-1.5 px-3 py-1 bg-primary-fixed text-on-primary-fixed-variant rounded-full text-xs font-medium hover:bg-primary-container hover:text-white transition-colors">
                                <span class="material-symbols-outlined text-sm" x-text="cat.icon"></span>
                                <span x-text="cat.name"></span>
                            </a>
                        </template>
                        <template x-for="tag in results.tags" :key="tag.id">
                            <a :href="tag.url" class="inline-flex items-center px-3 py-1 bg-surface-container text-on-surface-variant rounded-full text-xs font-mono-tech hover:bg-primary hover:text-white transition-colors">
                                #<span x-text="tag.name"></span>
                            </a>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer Help -->
        <div class="flex items-center justify-between px-4 py-2 bg-surface-container-low text-[11px] text-on-surface-variant">
            <span>Busca sin importar espacios · <b>ESC</b> para cerrar</span>
            <span>INNOVATEP Ideas</span>
        </div>
    </div>
</div>
