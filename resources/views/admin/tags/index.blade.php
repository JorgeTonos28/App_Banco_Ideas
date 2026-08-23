@extends('layouts.app')

@section('title', 'Gestión de Etiquetas - Administración INNOVATEP')

@section('content')
<div class="space-y-6" 
     x-data="{ 
        searchQuery: '',
        filterType: 'all',
        allTags: {{ json_encode($tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug, 'ideas_count' => (int)$t->ideas_count])->values()) }},
        mergeModal: false, 
        createModal: false, 
        editModal: false, 
        currentTag: { id: '', name: '', ideas_count: 0 },
        editNameInput: '',
        createNameInput: '',
        mergeSourceId: '',
        mergeTargetId: '',

        normalizeString(str) {
            if (!str) return '';
            return str.toString().trim()
                .toLowerCase()
                .replace(/^#+/, '')
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[-_]/g, ' ')
                .replace(/[^a-z0-9\s]/g, '')
                .replace(/\s+/g, ' ')
                .trim();
        },

        stemSpanish(word) {
            const norm = this.normalizeString(word);
            if (norm.length <= 3) return norm;
            if (norm.endsWith('ces') && norm.length > 4) {
                return norm.slice(0, -3) + 'z';
            }
            if (norm.endsWith('es') && norm.length > 4) {
                const base = norm.slice(0, -2);
                const last = base.slice(-1);
                if (['r', 'l', 'n', 'd', 'z', 'j', 'm'].includes(last)) return base;
            }
            if (norm.endsWith('s') && norm.length > 3) {
                const base = norm.slice(0, -1);
                const last = base.slice(-1);
                if (['a', 'e', 'i', 'o', 'u'].includes(last)) return base;
            }
            return norm;
        },

        levenshtein(a, b) {
            if (a.length === 0) return b.length;
            if (b.length === 0) return a.length;
            const matrix = [];
            for (let i = 0; i <= b.length; i++) matrix[i] = [i];
            for (let j = 0; j <= a.length; j++) matrix[0][j] = j;
            for (let i = 1; i <= b.length; i++) {
                for (let j = 1; j <= a.length; j++) {
                    if (b.charAt(i - 1) === a.charAt(j - 1)) {
                        matrix[i][j] = matrix[i - 1][j - 1];
                    } else {
                        matrix[i][j] = Math.min(
                            matrix[i - 1][j - 1] + 1,
                            matrix[i][j - 1] + 1,
                            matrix[i - 1][j] + 1
                        );
                    }
                }
            }
            return matrix[b.length][a.length];
        },

        calculateSimilarity(t1, t2) {
            const n1 = this.normalizeString(t1);
            const n2 = this.normalizeString(t2);
            if (!n1 || !n2) return 0;
            if (n1 === n2) return 1.0;
            
            const s1 = this.stemSpanish(t1);
            const s2 = this.stemSpanish(t2);
            if (s1 === s2 && s1.length >= 3) return 0.95;

            if (n1.length >= 4 && n2.length >= 4) {
                if (n1.includes(n2) || n2.includes(n1)) {
                    return Math.max(0.80, Math.min(n1.length, n2.length) / Math.max(n1.length, n2.length));
                }
            }

            const maxLen = Math.max(n1.length, n2.length);
            if (maxLen === 0) return 0;
            const dist = this.levenshtein(n1, n2);
            return Math.max(0, 1.0 - (dist / maxLen));
        },

        get filteredTags() {
            let list = this.allTags || [];
            
            // Search filter
            if (this.searchQuery && this.searchQuery.trim().length > 0) {
                const q = this.normalizeString(this.searchQuery);
                list = list.filter(t => this.normalizeString(t.name).includes(q) || (t.slug && t.slug.includes(q)));
            }

            // Status filter
            if (this.filterType === 'with_ideas') {
                list = list.filter(t => t.ideas_count > 0);
            } else if (this.filterType === 'without_ideas') {
                list = list.filter(t => t.ideas_count === 0);
            }

            return list;
        },

        get detectedSimilarInEdit() {
            const raw = (this.editNameInput || '').trim();
            if (raw.length < 2 || !this.currentTag.id) return [];
            
            const targetNorm = this.normalizeString(raw);
            const results = [];
            
            this.allTags.forEach(t => {
                if (t.id === this.currentTag.id) return;
                
                const tName = t.name || '';
                const tNorm = this.normalizeString(tName);
                
                const isExact = (tNorm === targetNorm);
                const sim = isExact ? 1.0 : this.calculateSimilarity(raw, tName);

                if (sim >= 0.68 || isExact) {
                    results.push({
                        ...t,
                        is_exact: isExact,
                        similarity: Math.round(sim * 100)
                    });
                }
            });

            return results.sort((a, b) => b.similarity - a.similarity).slice(0, 5);
        },

        get detectedSimilarInCreate() {
            const raw = (this.createNameInput || '').trim();
            if (raw.length < 2) return [];
            
            const targetNorm = this.normalizeString(raw);
            const results = [];
            
            this.allTags.forEach(t => {
                const tName = t.name || '';
                const tNorm = this.normalizeString(tName);
                
                const isExact = (tNorm === targetNorm);
                const sim = isExact ? 1.0 : this.calculateSimilarity(raw, tName);

                if (sim >= 0.68 || isExact) {
                    results.push({
                        ...t,
                        is_exact: isExact,
                        similarity: Math.round(sim * 100)
                    });
                }
            });

            return results.sort((a, b) => b.similarity - a.similarity).slice(0, 4);
        },

        openEdit(tag) {
            this.currentTag = { ...tag };
            this.editNameInput = tag.name;
            this.editModal = true;
        },

        openMergeWith(sourceId, targetId = '') {
            this.mergeSourceId = sourceId;
            this.mergeTargetId = targetId;
            this.mergeModal = true;
        }
     }">

    <!-- Header Section -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">Etiquetas y Palabras Clave</h1>
                <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Control de descriptores temáticos, edición y fusión de términos</p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" 
                        @click="mergeSourceId = ''; mergeTargetId = ''; mergeModal = true;" 
                        class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-headline font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5 shadow-2xs">
                    <span class="material-symbols-outlined text-sm text-primary">merge</span>
                    <span>Fusionar Etiquetas</span>
                </button>
                <button type="button" 
                        @click="createNameInput = ''; createModal = true;" 
                        class="px-4 py-2 bg-primary text-white font-headline font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">add</span>
                    <span>Nueva Etiqueta</span>
                </button>
            </div>
        </div>

        <x-admin-nav-tabs />
    </div>

    <!-- Live Search & Interactive Filter Bar -->
    <div class="bg-surface-container-lowest rounded-3xl p-5 border border-surface-container-high/80 shadow-xs space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            
            <!-- Dynamic Search Input -->
            <div class="relative flex-1">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-outline">search</span>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Buscar etiqueta por nombre (ej.: #IA, #Robótica, Sensores)..." 
                       class="w-full bg-surface-container-low text-on-surface text-xs sm:text-sm rounded-2xl pl-10 pr-10 py-3 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <button type="button" 
                        x-show="searchQuery.length > 0" 
                        @click="searchQuery = ''" 
                        class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-outline hover:text-on-surface rounded-full">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            </div>

            <!-- Filter Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
                <button type="button" 
                        @click="filterType = 'all'" 
                        :class="filterType === 'all' ? 'bg-primary text-white font-bold shadow-xs' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant'"
                        class="px-3 py-2 rounded-xl text-xs whitespace-nowrap transition-colors flex items-center gap-1.5 font-mono-tech">
                    <span>Todas</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="filterType === 'all' ? 'bg-white/20 text-white' : 'bg-surface-container-high text-outline'" x-text="allTags.length"></span>
                </button>

                <button type="button" 
                        @click="filterType = 'with_ideas'" 
                        :class="filterType === 'with_ideas' ? 'bg-primary text-white font-bold shadow-xs' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant'"
                        class="px-3 py-2 rounded-xl text-xs whitespace-nowrap transition-colors flex items-center gap-1.5 font-mono-tech">
                    <span>Con ideas</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="filterType === 'with_ideas' ? 'bg-white/20 text-white' : 'bg-surface-container-high text-outline'" x-text="allTags.filter(t => t.ideas_count > 0).length"></span>
                </button>

                <button type="button" 
                        @click="filterType = 'without_ideas'" 
                        :class="filterType === 'without_ideas' ? 'bg-primary text-white font-bold shadow-xs' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant'"
                        class="px-3 py-2 rounded-xl text-xs whitespace-nowrap transition-colors flex items-center gap-1.5 font-mono-tech">
                    <span>Sin ideas (0)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="filterType === 'without_ideas' ? 'bg-white/20 text-white' : 'bg-surface-container-high text-outline'" x-text="allTags.filter(t => t.ideas_count === 0).length"></span>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between text-xs text-on-surface-variant pt-1 border-t border-surface-container-high/60">
            <span class="font-mono-tech">
                Mostrando <strong class="text-on-surface" x-text="filteredTags.length"></strong> de <strong class="text-on-surface" x-text="allTags.length"></strong> etiquetas
            </span>
            <span x-show="searchQuery.length > 0" class="text-primary font-semibold text-[11px] flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">filter_alt</span>
                <span>Filtrando en tiempo real</span>
            </span>
        </div>
    </div>

    <!-- Tags Chips & Grid Display -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 border border-surface-container-high/80 shadow-xs space-y-4">
        
        <!-- Empty State -->
        <div x-show="filteredTags.length === 0" class="text-center py-12 space-y-3">
            <div class="w-12 h-12 rounded-full bg-surface-container mx-auto flex items-center justify-center text-outline">
                <span class="material-symbols-outlined text-2xl">search_off</span>
            </div>
            <div>
                <p class="text-sm font-semibold text-on-surface">No se encontraron etiquetas con el filtro actual</p>
                <p class="text-xs text-on-surface-variant mt-1" x-show="searchQuery.length > 0">
                    No existe ninguna etiqueta que coincida con "<span class="font-bold text-primary" x-text="searchQuery"></span>".
                </p>
            </div>
            <div x-show="searchQuery.length > 0" class="pt-2">
                <button type="button" 
                        @click="createNameInput = searchQuery; createModal = true;" 
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white font-headline font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container transition-colors">
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    <span>Crear "#<span x-text="searchQuery"></span>"</span>
                </button>
            </div>
        </div>

        <!-- Tags Grid -->
        <div class="flex flex-wrap gap-2.5 pt-1" x-show="filteredTags.length > 0">
            <template x-for="tag in filteredTags" :key="tag.id">
                <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-surface-container-low border border-surface-container-high text-xs group hover:border-primary/50 hover:bg-surface-container transition-all shadow-2xs">
                    <span class="font-mono-tech font-bold text-on-surface">#<span x-text="tag.name"></span></span>
                    
                    <span :class="tag.ideas_count > 0 ? 'bg-primary/10 text-primary font-bold' : 'bg-surface-container text-outline'"
                          class="px-2 py-0.5 rounded-full text-[10px] font-mono-tech"
                          :title="tag.ideas_count + ' ideas vinculadas'"
                          x-text="tag.ideas_count"></span>
                    
                    <div class="flex items-center gap-0.5 pl-1 border-l border-surface-container-high">
                        <!-- Edit Button -->
                        <button type="button" 
                                @click="openEdit(tag)" 
                                class="text-outline hover:text-primary p-1 rounded-lg hover:bg-surface-container-high transition-colors"
                                title="Editar nombre de la etiqueta">
                            <span class="material-symbols-outlined text-[13px]">edit</span>
                        </button>

                        <!-- Quick Merge Button -->
                        <button type="button" 
                                @click="openMergeWith(tag.id)" 
                                class="text-outline hover:text-amber-600 p-1 rounded-lg hover:bg-amber-50 transition-colors"
                                title="Fusionar con otra etiqueta">
                            <span class="material-symbols-outlined text-[13px]">merge</span>
                        </button>

                        <!-- Delete Button -->
                        <form :action="'/admin/etiquetas/' + tag.id" method="POST" :onsubmit="'return confirm(\'¿Eliminar etiqueta #' + tag.name.replace(/'/g, '\\\'') + '? Se desvinculará de las ideas asociadas.\');'" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-outline hover:text-error p-1 rounded-lg hover:bg-error/10 transition-colors" title="Eliminar etiqueta">
                                <span class="material-symbols-outlined text-[13px]">close</span>
                            </button>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Create Tag Modal with Similarity Detection -->
    <div x-show="createModal" 
         x-cloak
         @keydown.escape.window="createModal = false"
         class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center" 
         style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs transition-opacity" @click="createModal = false"></div>
        <div class="relative bg-surface-container-lowest rounded-3xl p-6 max-w-md w-full shadow-2xl border border-surface-container-high z-10 space-y-4">
            
            <div class="flex items-center justify-between border-b border-surface-container-high pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-lg">add</span>
                    </div>
                    <h3 class="font-headline font-bold text-base text-on-surface">Nueva Etiqueta</h3>
                </div>
                <button type="button" @click="createModal = false" class="text-outline hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <form action="{{ route('admin.tags.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Nombre del descriptor</label>
                    <input type="text" 
                           name="name" 
                           x-model="createNameInput" 
                           required 
                           placeholder="Ej.: Inteligencia Artificial" 
                           class="w-full bg-surface-container-low text-xs sm:text-sm rounded-xl p-3 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <!-- Live Similar Tags Alert in Create Modal -->
                <div x-show="detectedSimilarInCreate.length > 0" 
                     class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-xs space-y-2">
                    <div class="flex items-center gap-1.5 text-amber-900 font-bold text-[11px]">
                        <span class="material-symbols-outlined text-sm text-amber-600">compare_arrows</span>
                        <span>Etiquetas similares ya existentes en catálogo:</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="sim in detectedSimilarInCreate" :key="sim.id">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-surface-container-lowest border border-amber-300 text-on-surface text-xs font-mono-tech">
                                <span class="font-bold text-amber-800" x-text="'#' + sim.name"></span>
                                <span class="text-[10px] text-outline font-mono-tech" x-text="'(' + sim.ideas_count + ' ideas)'"></span>
                            </span>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-surface-container-high">
                    <button type="button" @click="createModal = false" class="px-4 py-2 text-xs font-semibold text-outline hover:text-on-surface">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 bg-primary text-white text-xs font-headline font-bold rounded-xl shadow-xs hover:bg-primary-container transition-colors">Crear Etiqueta</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Tag Modal with In-place Name Editing & Auto-Fusion Support -->
    <div x-show="editModal" 
         x-cloak
         @keydown.escape.window="editModal = false"
         class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center" 
         style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs transition-opacity" @click="editModal = false"></div>
        <div class="relative bg-surface-container-lowest rounded-3xl p-6 max-w-md w-full shadow-2xl border border-surface-container-high z-10 space-y-4">
            
            <div class="flex items-center justify-between border-b border-surface-container-high pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-lg">edit</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-bold text-base text-on-surface">Editar Etiqueta</h3>
                        <p class="text-[11px] text-on-surface-variant font-mono-tech">ID: <span x-text="currentTag.id"></span> • <span x-text="currentTag.ideas_count"></span> ideas asociadas</p>
                    </div>
                </div>
                <button type="button" @click="editModal = false" class="text-outline hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <form :action="'/admin/etiquetas/' + currentTag.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">Nombre corregido</label>
                    <input type="text" 
                           name="name" 
                           x-model="editNameInput" 
                           required 
                           placeholder="Nombre de la etiqueta"
                           class="w-full bg-surface-container-low text-xs sm:text-sm font-mono-tech font-bold text-on-surface rounded-xl p-3 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <!-- Similarity / Fusion Warning in Edit Modal -->
                <div x-show="detectedSimilarInEdit.length > 0" 
                     class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-xs space-y-2.5">
                    <div class="flex items-center justify-between text-amber-900 font-bold text-[11px]">
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm text-amber-600">compare_arrows</span>
                            <span>Etiquetas similares o coincidentes:</span>
                        </div>
                        <span class="text-[10px] font-mono-tech text-amber-800 bg-amber-200/60 px-2 py-0.5 rounded-full font-bold">
                            Fusión automática
                        </span>
                    </div>

                    <p class="text-[11px] text-amber-950 leading-relaxed">
                        Si guardas con el nombre exacto de una etiqueta existente, ambas se <strong class="underline">fusionarán automáticamente</strong> y se reasignarán todas sus ideas vinculadas.
                    </p>

                    <div class="flex flex-wrap gap-1.5 pt-1">
                        <template x-for="sim in detectedSimilarInEdit" :key="sim.id">
                            <button type="button" 
                                    @click="editNameInput = sim.name" 
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-surface-container-lowest hover:bg-amber-100 border border-amber-300 text-xs font-mono-tech text-on-surface transition-colors group"
                                    :title="'Clic para establecer nombre como #' + sim.name">
                                <span class="font-bold text-amber-800">#<span x-text="sim.name"></span></span>
                                <span class="text-[10px] px-1 py-0.2 rounded bg-amber-200 text-amber-900 font-bold" x-text="sim.ideas_count + ' ideas'"></span>
                                <span class="material-symbols-outlined text-xs text-amber-700 group-hover:scale-110">arrow_forward</span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-surface-container-high">
                    <button type="button" 
                            @click="editModal = false; openMergeWith(currentTag.id)" 
                            class="text-xs text-amber-800 hover:text-amber-950 font-bold flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">merge</span>
                        <span>Fusionar manualmente</span>
                    </button>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="editModal = false" class="px-4 py-2 text-xs font-semibold text-outline hover:text-on-surface">Cancelar</button>
                        <button type="submit" class="px-5 py-2.5 bg-primary text-white text-xs font-headline font-bold rounded-xl shadow-xs hover:bg-primary-container transition-colors">Guardar Cambios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Merge Tags Modal with Dynamic Selectors -->
    <div x-show="mergeModal" 
         x-cloak
         @keydown.escape.window="mergeModal = false"
         class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center" 
         style="display: none;">
        <div class="fixed inset-0 bg-on-surface/40 backdrop-blur-xs transition-opacity" @click="mergeModal = false"></div>
        <div class="relative bg-surface-container-lowest rounded-3xl p-6 max-w-md w-full shadow-2xl border border-surface-container-high z-10 space-y-4">
            
            <div class="flex items-center justify-between border-b border-surface-container-high pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-700">
                        <span class="material-symbols-outlined text-lg">merge</span>
                    </div>
                    <h3 class="font-headline font-bold text-base text-on-surface">Fusionar Etiquetas</h3>
                </div>
                <button type="button" @click="mergeModal = false" class="text-outline hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <p class="text-xs text-on-surface-variant leading-relaxed">
                Todas las propuestas vinculadas a la <strong class="text-on-surface">etiqueta origen</strong> se reasignarán a la <strong class="text-primary">etiqueta destino</strong>, eliminando la duplicada de forma segura.
            </p>

            <form action="{{ route('admin.tags.merge') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">
                        Etiqueta de Origen (Será eliminada) <span class="text-error">*</span>
                    </label>
                    <select name="source_tag_id" x-model="mergeSourceId" required class="w-full bg-surface-container-low text-xs sm:text-sm rounded-xl p-3 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono-tech">
                        <option value="">Selecciona la etiqueta origen</option>
                        <template x-for="t in allTags" :key="t.id">
                            <option :value="t.id" :selected="mergeSourceId == t.id" x-text="'#' + t.name + ' (' + t.ideas_count + ' ideas)'"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface uppercase font-mono-tech mb-1">
                        Etiqueta de Destino (Permanecerá) <span class="text-error">*</span>
                    </label>
                    <select name="target_tag_id" x-model="mergeTargetId" required class="w-full bg-surface-container-low text-xs sm:text-sm rounded-xl p-3 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono-tech">
                        <option value="">Selecciona la etiqueta destino</option>
                        <template x-for="t in allTags" :key="t.id">
                            <option :value="t.id" :disabled="mergeSourceId == t.id" :selected="mergeTargetId == t.id" x-text="'#' + t.name + ' (' + t.ideas_count + ' ideas)'"></option>
                        </template>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-surface-container-high">
                    <button type="button" @click="mergeModal = false" class="px-4 py-2 text-xs font-semibold text-outline hover:text-on-surface">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 bg-primary text-white text-xs font-headline font-bold rounded-xl shadow-xs hover:bg-primary-container transition-colors">Confirmar Fusión</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
