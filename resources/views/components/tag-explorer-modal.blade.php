<!-- Tag Explorer Modal Component -->
<div x-show="openTagModal" 
     x-cloak
     @keydown.escape.window="openTagModal = false"
     class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-6 md:p-10 flex items-center justify-center" 
     style="display: none;">

    <!-- Backdrop -->
    <div x-show="openTagModal" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         @click="openTagModal = false" 
         class="fixed inset-0 bg-on-surface/50 backdrop-blur-xs transition-opacity"></div>

    <!-- Modal Dialog Box -->
    <div x-show="openTagModal" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0 scale-95 translate-y-2" 
         x-transition:enter-end="opacity-100 scale-100 translate-y-0" 
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100 scale-100 translate-y-0" 
         x-transition:leave-end="opacity-0 scale-95 translate-y-2" 
         class="relative bg-surface-container-lowest rounded-3xl shadow-2xl border border-surface-container-high w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden z-10">

        <!-- Header -->
        <div class="p-5 sm:px-6 sm:py-5 border-b border-surface-container-high flex items-center justify-between bg-surface/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary-fixed flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-xl">tag</span>
                </div>
                <div>
                    <h3 class="font-headline font-extrabold text-base sm:text-lg text-on-surface">Explorador de Etiquetas</h3>
                    <p class="text-xs text-on-surface-variant">Selecciona descriptores existentes o busca términos temáticos</p>
                </div>
            </div>
            <button type="button" 
                    @click="openTagModal = false" 
                    class="p-2 rounded-xl text-outline hover:text-on-surface hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <!-- Search Bar -->
        <div class="p-4 sm:px-6 border-b border-surface-container-high bg-surface-container-lowest">
            <div class="relative flex items-center">
                <span class="material-symbols-outlined absolute left-3.5 text-lg text-outline">search</span>
                <input type="text" 
                       x-model="modalSearch" 
                       placeholder="Escribe para buscar etiquetas (ej.: Automatización, IA, Talleres)..."
                       class="w-full bg-surface-container-low text-on-surface text-xs sm:text-sm rounded-2xl pl-10 pr-10 py-3 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <button type="button" 
                        x-show="modalSearch.length > 0" 
                        @click="modalSearch = ''" 
                        class="absolute right-3 p-1 text-outline hover:text-on-surface">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            </div>
        </div>

        <!-- View / Filter Tabs -->
        <div class="px-4 sm:px-6 pt-3 pb-2 border-b border-surface-container-high bg-surface-container-low/40 flex flex-wrap items-center gap-1.5 sm:gap-2">
            <!-- Tab: Alphabetical A-Z -->
            <button type="button" 
                    @click="modalTab = 'alphabetical'; modalSearch = ''" 
                    :class="modalTab === 'alphabetical' && !modalSearch ? 'bg-primary text-white shadow-xs font-bold' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant font-medium'"
                    class="px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-sm">sort_by_alpha</span>
                <span>Alfabético (A - Z)</span>
            </button>

            <!-- Tab: By Category -->
            <button type="button" 
                    @click="modalTab = 'categories'; modalSearch = ''" 
                    :class="modalTab === 'categories' && !modalSearch ? 'bg-primary text-white shadow-xs font-bold' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant font-medium'"
                    class="px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-sm">category</span>
                <span>Por Categoría</span>
            </button>

            <!-- Tab: Popular -->
            <button type="button" 
                    @click="modalTab = 'popular'; modalSearch = ''" 
                    :class="modalTab === 'popular' && !modalSearch ? 'bg-primary text-white shadow-xs font-bold' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant font-medium'"
                    class="px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-sm">trending_up</span>
                <span>Más Populares</span>
            </button>

            <!-- Tab: Recommended for this idea -->
            <button type="button" 
                    @click="modalTab = 'suggested'; modalSearch = ''" 
                    :class="modalTab === 'suggested' && !modalSearch ? 'bg-primary text-white shadow-xs font-bold' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant font-medium'"
                    class="px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-sm text-secondary-container">auto_awesome</span>
                <span>Recomendadas</span>
                <template x-if="suggestedTags.length > 0">
                    <span class="px-1.5 py-0.2 rounded-full bg-white/20 text-[10px] font-mono-tech" x-text="suggestedTags.length"></span>
                </template>
            </button>
        </div>

        <!-- Sub-filters (Category selection or Alphabet index) -->
        <div class="px-4 sm:px-6 py-2.5 bg-surface-container-low/20 border-b border-surface-container-high text-xs">
            
            <!-- Category Sub-filter (When in Categories Tab) -->
            <div x-show="modalTab === 'categories' && !modalSearch" class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
                <button type="button" 
                        @click="selectedCategoryFilter = null"
                        :class="selectedCategoryFilter === null ? 'bg-primary-container text-white font-bold' : 'bg-surface-container hover:bg-surface-container-high text-on-surface'"
                        class="px-2.5 py-1 rounded-lg text-[11px] whitespace-nowrap transition-colors">
                    Todas las categorías
                </button>
                <template x-for="cat in categories" :key="cat.id">
                    <button type="button" 
                            @click="selectedCategoryFilter = cat.id"
                            :class="selectedCategoryFilter === cat.id ? 'bg-primary-container text-white font-bold' : 'bg-surface-container hover:bg-surface-container-high text-on-surface'"
                            class="px-2.5 py-1 rounded-lg text-[11px] whitespace-nowrap flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-[13px]" x-text="cat.icon || 'folder'"></span>
                        <span x-text="cat.name"></span>
                    </button>
                </template>
            </div>

            <!-- Letter Index Sub-filter (When in Alphabetical Tab) -->
            <div x-show="modalTab === 'alphabetical' && !modalSearch" class="flex items-center gap-1 overflow-x-auto pb-1 scrollbar-thin">
                <button type="button" 
                        @click="selectedLetter = 'ALL'" 
                        :class="selectedLetter === 'ALL' ? 'bg-primary-container text-white font-bold' : 'bg-surface-container text-on-surface hover:bg-surface-container-high'"
                        class="px-2 py-0.5 rounded-md text-[11px] font-mono-tech transition-colors">
                    TODAS
                </button>
                <template x-for="let in availableLetters" :key="let">
                    <button type="button" 
                            @click="selectedLetter = let" 
                            :class="selectedLetter === let ? 'bg-primary-container text-white font-bold' : 'bg-surface-container text-on-surface hover:bg-surface-container-high'"
                            class="px-1.5 py-0.5 rounded-md text-[11px] font-mono-tech transition-colors"
                            x-text="let">
                    </button>
                </template>
            </div>

            <!-- Context note for Suggested Tab -->
            <div x-show="modalTab === 'suggested' && !modalSearch" class="text-on-surface-variant text-[11px] flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm text-primary">info</span>
                <span>Etiquetas detectadas según tu título, descripción y categoría seleccionada.</span>
            </div>

            <!-- Context note for Popular Tab -->
            <div x-show="modalTab === 'popular' && !modalSearch" class="text-on-surface-variant text-[11px] flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm text-primary">trending_up</span>
                <span>Etiquetas ordenadas por cantidad de ideas registradas en el banco institucional.</span>
            </div>

            <!-- Context note for Search mode -->
            <div x-show="modalSearch.length > 0" class="text-on-surface-variant text-[11px] flex items-center justify-between">
                <span>Resultados de búsqueda para "<strong class="text-on-surface" x-text="modalSearch"></strong>":</span>
                <span class="font-mono-tech font-bold text-primary" x-text="filteredTags.length + ' encontradas'"></span>
            </div>
        </div>

        <!-- Tags List Area (Scrollable Body) -->
        <div class="p-4 sm:p-6 overflow-y-auto max-h-[50vh] flex-1 space-y-6">

            <!-- Similar Tags Suggestion in Search Mode (Fuzzy / Similarity) -->
            <div x-show="modalSearch.length >= 2 && modalSimilarTags.length > 0" 
                 class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 space-y-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-amber-900">
                        <span class="material-symbols-outlined text-sm text-amber-600">compare_arrows</span>
                        <span>Etiquetas similares ya existentes en la comunidad:</span>
                    </div>
                    <span class="text-[10px] font-mono-tech text-amber-800 bg-amber-200/60 px-2 py-0.5 rounded-full font-bold">
                        Recomendadas para evitar duplicados
                    </span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="sim in modalSimilarTags" :key="sim.id">
                        <button type="button" 
                                @click="toggleTag(sim.name)" 
                                :class="isTagSelected(sim.name) ? 'bg-primary text-white border-primary shadow-xs' : 'bg-surface-container-lowest hover:bg-amber-100 border-amber-300 text-on-surface'"
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-medium transition-all group">
                            <span class="font-mono-tech font-bold text-amber-800" x-text="'#' + sim.name"></span>
                            <span class="px-1.5 py-0.5 rounded-md bg-amber-200 text-amber-900 text-[10px] font-mono-tech font-bold" x-text="sim.ideas_count + ' ideas'"></span>
                            <span class="material-symbols-outlined text-xs text-primary" x-show="isTagSelected(sim.name)">check</span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Empty Search State & Create Action -->
            <div x-show="filteredTags.length === 0" class="text-center py-8 space-y-3">
                <div class="w-12 h-12 rounded-full bg-surface-container mx-auto flex items-center justify-center text-outline">
                    <span class="material-symbols-outlined text-2xl">search_off</span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-on-surface">No se encontraron etiquetas exactas</p>
                    <p class="text-xs text-on-surface-variant mt-1" x-show="modalSearch.length > 0">
                        ¿Deseas registrar "<span class="font-bold text-primary" x-text="modalSearch"></span>" como una nueva etiqueta?
                    </p>
                </div>
                <div x-show="modalSearch.length > 0" class="pt-2">
                    <button type="button" 
                            @click="addCustomTag(modalSearch); openTagModal = false;" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-headline font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container transition-colors">
                        <span class="material-symbols-outlined text-sm">add_circle</span>
                        <span>Crear y seleccionar "#<span x-text="modalSearch"></span>"</span>
                    </button>
                </div>
            </div>

            <!-- Alphabetical Grouped Display (When in Alphabetical mode and NO specific search active) -->
            <template x-if="modalTab === 'alphabetical' && !modalSearch && filteredTags.length > 0">
                <div class="space-y-6">
                    <template x-for="(groupTags, letter) in alphabeticalGroups" :key="letter">
                        <div class="space-y-2.5">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-surface-container flex items-center justify-center text-xs font-mono-tech font-bold text-primary" x-text="letter"></span>
                                <div class="h-px bg-surface-container-high flex-1"></div>
                            </div>
                            <div class="flex flex-wrap gap-2 pt-1">
                                <template x-for="tag in groupTags" :key="tag.id">
                                    <button type="button" 
                                            @click="toggleTag(tag.name)" 
                                            :class="isTagSelected(tag.name) ? 'bg-primary text-white border-primary shadow-xs' : 'bg-surface-container-low hover:bg-surface-container text-on-surface border-surface-container-high'"
                                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-medium transition-all group">
                                        <span class="font-mono-tech" x-text="'#' + tag.name"></span>
                                        <span :class="isTagSelected(tag.name) ? 'bg-white/20 text-white' : 'bg-surface-container text-outline'"
                                              class="px-1.5 py-0.5 rounded-md text-[10px] font-mono-tech font-bold"
                                              x-text="tag.ideas_count"></span>
                                        <span class="material-symbols-outlined text-xs" x-show="isTagSelected(tag.name)">check</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Flat Grid Display (For Categories, Popular, Suggested or active Search) -->
            <template x-if="(modalTab !== 'alphabetical' || modalSearch) && filteredTags.length > 0">
                <div class="flex flex-wrap gap-2">
                    <template x-for="tag in filteredTags" :key="tag.id">
                        <button type="button" 
                                @click="toggleTag(tag.name)" 
                                :class="isTagSelected(tag.name) ? 'bg-primary text-white border-primary shadow-xs' : 'bg-surface-container-low hover:bg-surface-container text-on-surface border-surface-container-high'"
                                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border text-xs font-medium transition-all group">
                            <span class="font-mono-tech" x-text="'#' + tag.name"></span>
                            <span :class="isTagSelected(tag.name) ? 'bg-white/20 text-white' : 'bg-surface-container text-outline'"
                                  class="px-1.5 py-0.5 rounded-md text-[10px] font-mono-tech font-bold"
                                  x-text="tag.ideas_count"></span>
                            <span class="material-symbols-outlined text-xs" x-show="isTagSelected(tag.name)">check</span>
                        </button>
                    </template>
                </div>
            </template>

        </div>

        <!-- Footer / Action Bar -->
        <div class="p-4 sm:px-6 border-t border-surface-container-high bg-surface/50 flex items-center justify-between gap-4">
            <div class="text-xs text-on-surface-variant flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full bg-primary"></span>
                <span>
                    <strong class="text-on-surface font-mono-tech" x-text="tagsList.length"></strong>
                    <span x-text="tagsList.length === 1 ? 'etiqueta seleccionada' : 'etiquetas seleccionadas'"></span>
                </span>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" 
                        @click="tagsList = []" 
                        x-show="tagsList.length > 0"
                        class="px-3 py-2 text-xs font-semibold text-outline hover:text-error transition-colors">
                    Limpiar selección
                </button>
                <button type="button" 
                        @click="openTagModal = false" 
                        class="px-5 py-2.5 bg-primary text-white font-headline font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container transition-colors">
                    Listo / Aplicar
                </button>
            </div>
        </div>

    </div>

</div>
