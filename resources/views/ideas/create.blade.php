@extends('layouts.app')

@section('title', 'Compartir una Idea - INNOVATEP')

@section('content')
<div class="max-w-3xl mx-auto space-y-6"
     @ai-tags-suggested.window="tagsList = [...new Set($event.detail.names)]"
     x-data="{ 
        tagsList: {{ json_encode(array_values(old('tags', []))) }}, 
        tagInput: '',
        openTagModal: false,
        modalSearch: '',
        modalTab: 'alphabetical',
        selectedCategoryFilter: null,
        selectedLetter: 'ALL',
        selectedCategoryId: '{{ old('category_id', '') }}',
        accessScope: @js(old('access_scope', 'only_me')),
        visibilityState: @js(old('visibility', 'private')),
        titleText: '',
        descriptionText: '',
        editingTagIdx: null,
        editingTagValue: '',
        allTags: {{ json_encode($allTags->values()) }},
        categories: {{ json_encode($categories->map(fn($c) => ['id' => (int)$c->id, 'name' => (string)$c->name, 'icon' => (string)($c->icon ?? 'folder'), 'color' => (string)($c->color ?? '#003e6f')])->values()) }},

        init() {
            if (this.$refs.titleInput) this.titleText = this.$refs.titleInput.value || '';
            if (this.$refs.descriptionInput) this.descriptionText = this.$refs.descriptionInput.value || '';
            const catEl = document.getElementById('category_id');
            if (catEl && catEl.value) this.selectedCategoryId = catEl.value;
        },

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

        get detectedSimilarTags() {
            const raw = (this.tagInput || '').trim();
            if (raw.length < 2) return [];
            
            const targetNorm = this.normalizeString(raw);
            const results = [];
            
            (this.allTags || []).forEach(t => {
                const tName = t.name || '';
                const tNorm = this.normalizeString(tName);
                
                if (this.tagsList.includes(tName)) return;
                if (tNorm === targetNorm) return;

                const sim = this.calculateSimilarity(raw, tName);
                if (sim >= 0.68) {
                    results.push({
                        ...t,
                        similarity: Math.round(sim * 100)
                    });
                }
            });

            return results.sort((a, b) => b.similarity - a.similarity).slice(0, 4);
        },

        toggleTag(tagName) {
            const index = this.tagsList.indexOf(tagName);
            if (index === -1) {
                this.tagsList.push(tagName);
            } else {
                this.tagsList.splice(index, 1);
            }
        },

        isTagSelected(tagName) {
            return this.tagsList.includes(tagName);
        },

        startEditTag(idx) {
            this.editingTagIdx = idx;
            this.editingTagValue = this.tagsList[idx];
            this.$nextTick(() => {
                const input = document.getElementById('editTagInput_' + idx);
                if (input) {
                    input.focus();
                    input.select();
                }
            });
        },

        saveEditTag(idx) {
            if (this.editingTagIdx === null) return;
            const raw = (this.editingTagValue || '').trim();
            if (!raw) {
                this.tagsList.splice(idx, 1);
                this.editingTagIdx = null;
                return;
            }
            
            const cleanNorm = this.normalizeString(raw);
            const existing = (this.allTags || []).find(t => 
                this.normalizeString(t.name) === cleanNorm || 
                (t.slug && t.slug === cleanNorm.replace(/\s+/g, '-'))
            );
            const finalName = existing ? existing.name : raw.replace(/^#+/, '').trim();
            
            this.tagsList[idx] = finalName;
            
            if (!existing && !this.allTags.some(t => this.normalizeString(t.name) === cleanNorm)) {
                this.allTags.push({
                    id: Date.now() + Math.floor(Math.random() * 1000),
                    name: finalName,
                    slug: cleanNorm.replace(/\s+/g, '-'),
                    ideas_count: 0,
                    category_ids: this.selectedCategoryId ? [parseInt(this.selectedCategoryId)] : []
                });
            }
            
            this.editingTagIdx = null;
            this.editingTagValue = '';
        },

        cancelEditTag() {
            this.editingTagIdx = null;
            this.editingTagValue = '';
        },

        get filteredTags() {
            let list = this.allTags || [];
            if (this.modalSearch && this.modalSearch.trim().length > 0) {
                const q = this.modalSearch.toLowerCase().trim();
                return list.filter(t => (t.name || '').toLowerCase().includes(q));
            }
            if (this.modalTab === 'categories') {
                if (this.selectedCategoryFilter) {
                    return list.filter(t => t.category_ids && t.category_ids.includes(parseInt(this.selectedCategoryFilter)));
                }
                return list;
            }
            if (this.modalTab === 'popular') {
                return [...list].sort((a, b) => (b.ideas_count || 0) - (a.ideas_count || 0));
            }
            if (this.modalTab === 'suggested') {
                return this.suggestedTags;
            }
            if (this.selectedLetter !== 'ALL') {
                return list.filter(t => t.name && t.name.toUpperCase().startsWith(this.selectedLetter));
            }
            return list;
        },

        get modalSimilarTags() {
            const q = (this.modalSearch || '').trim();
            if (q.length < 2) return [];
            const qNorm = this.normalizeString(q);
            const results = [];
            
            (this.allTags || []).forEach(t => {
                const tName = t.name || '';
                const tNorm = this.normalizeString(tName);
                if (tNorm === qNorm) return;
                if (tNorm.includes(qNorm)) return;

                const sim = this.calculateSimilarity(q, tName);
                if (sim >= 0.68) {
                    results.push({
                        ...t,
                        similarity: Math.round(sim * 100)
                    });
                }
            });
            return results.sort((a, b) => b.similarity - a.similarity).slice(0, 6);
        },

        get alphabeticalGroups() {
            const groups = {};
            const tags = this.filteredTags;
            tags.forEach(t => {
                const letter = (t.name && t.name[0] ? t.name[0] : '#').toUpperCase();
                if (!groups[letter]) groups[letter] = [];
                groups[letter].push(t);
            });
            return groups;
        },

        get availableLetters() {
            const letters = new Set();
            (this.allTags || []).forEach(t => {
                if (t.name && t.name[0]) letters.add(t.name[0].toUpperCase());
            });
            return Array.from(letters).sort();
        },

        get suggestedTags() {
            const suggestions = [];
            const seen = new Set();

            // 1. By selected category
            if (this.selectedCategoryId) {
                const catId = parseInt(this.selectedCategoryId);
                (this.allTags || []).forEach(t => {
                    if (t.category_ids && t.category_ids.includes(catId) && !seen.has(t.name)) {
                        seen.add(t.name);
                        suggestions.push(t);
                    }
                });
            }

            // 2. Text keyword match from title & description
            const text = ((this.titleText || '') + ' ' + (this.descriptionText || '')).toLowerCase();
            if (text.trim().length >= 3) {
                (this.allTags || []).forEach(t => {
                    const tName = (t.name || '').toLowerCase();
                    if (tName.length >= 3 && text.includes(tName) && !seen.has(t.name)) {
                        seen.add(t.name);
                        suggestions.push(t);
                    }
                });
            }

            return suggestions;
        },

        get dynamicSuggestionsNotSelected() {
            return this.suggestedTags.filter(t => !this.tagsList.includes(t.name)).slice(0, 8);
        },

        addCustomTag(name) {
            const raw = (name || this.tagInput || this.modalSearch || '').trim();
            if (!raw) return;

            const parts = raw.split(',')
                .map(s => s.trim().replace(/^#+/, ''))
                .filter(s => s.length > 0);

            parts.forEach(clean => {
                const cleanNorm = this.normalizeString(clean);
                
                // Find existing canonical tag (casing/accent normalization)
                const existing = (this.allTags || []).find(t => 
                    this.normalizeString(t.name) === cleanNorm || 
                    (t.slug && t.slug === cleanNorm.replace(/\s+/g, '-'))
                );
                
                const finalName = existing ? existing.name : clean;

                if (!this.tagsList.includes(finalName)) {
                    this.tagsList.push(finalName);
                    if (!existing && !this.allTags.some(t => this.normalizeString(t.name) === cleanNorm)) {
                        this.allTags.push({
                            id: Date.now() + Math.floor(Math.random() * 1000),
                            name: finalName,
                            slug: cleanNorm.replace(/\s+/g, '-'),
                            ideas_count: 0,
                            category_ids: this.selectedCategoryId ? [parseInt(this.selectedCategoryId)] : []
                        });
                    }
                }
            });

            this.tagInput = '';
            this.modalSearch = '';
        }
     }">

    <!-- Header Section -->
    <div class="text-center sm:text-left">
        <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">Comparte tu idea</h1>
        <p class="text-xs sm:text-sm text-on-surface-variant mt-1.5 leading-relaxed">
            No tiene que estar perfectamente desarrollada. Empieza contándonos qué tienes en mente y la comunidad te ayudará a impulsarla.
        </p>
    </div>

    <!-- Main Creation Form Card -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-10 border border-surface-container-high/80 shadow-xs">
        
        @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-error-container/60 border border-error/30 text-error text-xs font-medium space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">error</span>
                <span>Por favor revisa los siguientes campos:</span>
            </div>
            <ul class="list-disc list-inside pl-2 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('ideas.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <x-ai-idea-assistant />

            <!-- Title -->
            <div>
                <label for="title" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                    Título de la idea <span class="text-error">*</span>
                </label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       x-ref="titleInput"
                       @input="titleText = $event.target.value"
                       value="{{ old('title') }}" 
                       required 
                       placeholder="Ej.: Digitalizar el registro de mantenimiento de los talleres"
                       class="w-full bg-surface-container-low text-on-surface text-sm sm:text-base rounded-2xl py-3 px-4 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                    Describe tu idea <span class="text-error">*</span>
                </label>
                <textarea id="description" 
                          name="description" 
                          x-ref="descriptionInput"
                          @input="descriptionText = $event.target.value"
                          rows="5" 
                          required 
                          placeholder="¿Qué propones? ¿Cómo funcionaría en el día a día?"
                          class="w-full bg-surface-container-low text-on-surface text-sm rounded-2xl p-4 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-y">{{ old('description') }}</textarea>
            </div>

            <!-- Problem / Opportunity -->
            <div>
                <label for="problem_opportunity" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                    ¿Qué problema u oportunidad busca resolver?
                </label>
                <textarea id="problem_opportunity" 
                          name="problem_opportunity" 
                          rows="3" 
                          placeholder="Cuéntanos qué situación observaste en aulas, talleres o procesos y por qué vale la pena mejorarla."
                          class="w-full bg-surface-container-low text-on-surface text-sm rounded-2xl p-4 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-y">{{ old('problem_opportunity') }}</textarea>
            </div>

            <x-classification-guidance />

            <!-- Category -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div>
                    <label for="category_id" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                        Categoría <span class="text-error">*</span>
                    </label>
                    <select id="category_id" 
                            name="category_id" 
                            @change="selectedCategoryId = $event.target.value"
                            required 
                            class="w-full bg-surface-container-low text-on-surface text-sm rounded-2xl p-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Selecciona una categoría</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string)old('category_id') === (string)$cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="visibility" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                        Estado de preparación <span class="text-error">*</span>
                    </label>
                    <select id="visibility" 
                            name="visibility" 
                            x-model="visibilityState"
                            class="w-full bg-surface-container-low text-on-surface text-sm rounded-2xl p-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="private" {{ old('visibility', 'private') == 'private' ? 'selected' : '' }}>Idea completa</option>
                        <option value="draft" {{ old('visibility', 'private') == 'draft' ? 'selected' : '' }}>Borrador incompleto</option>
                    </select>
                    <p class="mt-1.5 text-[11px] text-on-surface-variant">Los borradores siempre son visibles sólo para ti.</p>
                </div>

                <div>
                    <label for="access_scope" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                        Quién puede verla <span class="text-error">*</span>
                    </label>
                    <select id="access_scope"
                            name="access_scope"
                            x-model="accessScope"
                            class="w-full bg-surface-container-low text-on-surface text-sm rounded-2xl p-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="only_me" {{ old('access_scope', 'only_me') === 'only_me' ? 'selected' : '' }}>Sólo yo</option>
                        <option value="profile" {{ old('access_scope') === 'profile' ? 'selected' : '' }}>Visible en mi perfil</option>
                        @if($communityUnits->isNotEmpty())
                        <option value="organization" {{ old('access_scope') === 'organization' ? 'selected' : '' }}>Mi comunidad interna</option>
                        @endif
                    </select>
                    <p class="mt-1.5 text-[11px] text-on-surface-variant">Compartir en tu perfil no publica la idea en Comunidad.</p>
                    @error('access_scope')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>

            @if($communityUnits->isNotEmpty())
            <div x-show="accessScope === 'organization'" x-cloak class="rounded-2xl border border-primary/15 bg-primary-fixed/25 p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined mt-0.5 text-xl text-primary" aria-hidden="true">corporate_fare</span>
                    <div class="min-w-0 flex-1 space-y-4">
                        <div>
                            <h3 class="text-xs font-bold text-on-surface">Audiencia de la comunidad interna</h3>
                            <p class="mt-1 text-[11px] leading-relaxed text-on-surface-variant">Esta visibilidad no requiere revisión editorial y mantiene el estado de trabajo privado.</p>
                        </div>
                        <div>
                            <label for="organizational_unit_id" class="mb-1.5 block text-xs font-bold text-on-surface">Comunidad visible</label>
                            <select id="organizational_unit_id" name="organizational_unit_id" :required="accessScope === 'organization'" class="w-full rounded-xl border border-surface-container-high bg-surface-container-lowest p-3 text-xs text-on-surface">
                                <option value="">Selecciona una comunidad</option>
                                @foreach($communityUnits as $unit)
                                    <option value="{{ $unit->id }}" {{ (string) old('organizational_unit_id') === (string) $unit->id ? 'selected' : '' }}>{{ $unit->path_label }}</option>
                                @endforeach
                            </select>
                            @error('organizational_unit_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <label class="flex cursor-pointer items-start gap-2.5 text-xs text-on-surface">
                            <input type="checkbox" name="include_descendants" value="1" {{ old('include_descendants') ? 'checked' : '' }} class="mt-0.5 rounded border-outline text-primary focus:ring-primary">
                            <span><strong class="block">Incluir niveles dependientes</strong><span class="mt-0.5 block text-[10px] text-on-surface-variant">Por ejemplo, una dirección podrá incluir a sus departamentos.</span></span>
                        </label>
                    </div>
                </div>
            </div>
            @endif

            <div>
                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                    Idea madre <span class="text-outline normal-case tracking-normal">(opcional)</span>
                </label>
                <x-idea-parent-picker :candidates="$parentCandidates" :selected-id="$selectedParentId" />
                <p class="mt-1.5 text-[11px] text-on-surface-variant">Úsala cuando esta propuesta sea una parte, línea de trabajo o derivación de otra idea.</p>
                @error('parent_idea_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <x-idea-classification-fields :dimensions="$categoryDimensions" />

            <!-- Tags Input with Chips, In-place Editing, Real-time Similarity Detection & Modal Explorer -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech">
                        Etiquetas o Palabras Clave
                    </label>
                    <button type="button" 
                            @click="openTagModal = true" 
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-primary/10 hover:bg-primary/20 text-primary font-headline font-bold text-xs transition-colors">
                        <span class="material-symbols-outlined text-sm">explore</span>
                        <span>Ver todas las etiquetas</span>
                        <span class="px-1.5 py-0.2 rounded-full bg-primary text-white text-[10px] font-mono-tech font-bold" x-text="allTags.length"></span>
                    </button>
                </div>

                <div class="space-y-2.5">
                    <div class="flex items-center gap-2">
                        <input type="text" 
                               x-model="tagInput" 
                               @keydown.enter.prevent="addCustomTag(tagInput)"
                               @input="if (tagInput.includes(',')) { addCustomTag(tagInput); }"
                               placeholder="Escribe etiquetas (puedes separar por comas: IA, Procesos, Digital) y presiona Enter"
                               class="flex-1 bg-surface-container-low text-on-surface text-xs rounded-xl py-2.5 px-3.5 border border-surface-container-high focus:outline-none focus:ring-1 focus:ring-primary">
                        <button type="button" 
                                @click="addCustomTag(tagInput)"
                                class="px-4 py-2.5 bg-surface-container hover:bg-surface-container-high text-xs font-semibold text-on-surface rounded-xl transition-colors">
                            Agregar
                        </button>
                    </div>

                    <!-- Real-Time Similar Tags Detection Banner -->
                    <div x-show="detectedSimilarTags.length > 0" 
                         x-transition 
                         class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-xs space-y-2">
                        <div class="flex items-center justify-between text-amber-800 font-semibold text-[11px]">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm text-amber-600">compare_arrows</span>
                                <span>Detectamos etiquetas similares ya registradas:</span>
                            </div>
                            <span class="text-[10px] font-mono-tech text-amber-700 bg-amber-200/60 px-2 py-0.5 rounded-full font-bold">
                                Evita duplicados
                            </span>
                        </div>
                        
                        <div class="flex flex-wrap gap-1.5 items-center">
                            <template x-for="sim in detectedSimilarTags" :key="sim.id">
                                <button type="button" 
                                        @click="toggleTag(sim.name); tagInput = '';" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container-lowest hover:bg-amber-100 border border-amber-300 text-on-surface text-xs font-mono-tech font-bold transition-all shadow-2xs group"
                                        :title="'Coincidencia ' + sim.similarity + '%'">
                                    <span class="text-amber-700">#<span x-text="sim.name"></span></span>
                                    <span class="px-1.5 py-0.2 rounded-md bg-amber-200 text-amber-900 text-[10px]" x-text="sim.ideas_count + ' ideas'"></span>
                                    <span class="material-symbols-outlined text-xs text-amber-700 group-hover:scale-110 transition-transform">check_circle</span>
                                </button>
                            </template>
                            <button type="button" 
                                    @click="addCustomTag(tagInput)" 
                                    class="text-[11px] text-amber-800 underline hover:text-amber-950 font-medium pl-1">
                                Mantener nueva de todos modos
                            </button>
                        </div>
                    </div>

                    <!-- Selected Tags Chips List with In-place Editing -->
                    <div class="flex flex-wrap gap-2 pt-1 min-h-[32px] items-center">
                        <template x-for="(tag, idx) in tagsList" :key="idx">
                            <div class="relative">
                                <!-- Normal Chip Mode -->
                                <template x-if="editingTagIdx !== idx">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary-fixed text-on-primary-fixed-variant text-xs font-mono-tech group transition-all hover:shadow-2xs">
                                        <span x-text="'#' + tag" @dblclick="startEditTag(idx)" class="cursor-pointer select-none font-bold" title="Doble clic o clic en el lápiz para editar"></span>
                                        <input type="hidden" name="tags[]" :value="tag">
                                        
                                        <!-- Edit Button -->
                                        <button type="button" 
                                                @click="startEditTag(idx)" 
                                                class="text-on-primary-fixed-variant/60 hover:text-primary p-0.5 rounded transition-colors"
                                                title="Editar esta etiqueta">
                                            <span class="material-symbols-outlined text-[13px]">edit</span>
                                        </button>
                                        
                                        <!-- Remove Button -->
                                        <button type="button" 
                                                @click="tagsList.splice(idx, 1)" 
                                                class="text-on-primary-fixed-variant/60 hover:text-error p-0.5 rounded transition-colors"
                                                title="Quitar etiqueta">
                                            <span class="material-symbols-outlined text-[13px]">close</span>
                                        </button>
                                    </span>
                                </template>

                                <!-- Inline Editing Mode -->
                                <template x-if="editingTagIdx === idx">
                                    <div class="inline-flex items-center gap-1 p-1 bg-surface-container-lowest border-2 border-primary rounded-xl shadow-xs">
                                        <span class="text-xs font-mono-tech text-primary font-bold pl-1.5">#</span>
                                        <input type="text" 
                                               x-model="editingTagValue" 
                                               :id="'editTagInput_' + idx"
                                               @keydown.enter.prevent="saveEditTag(idx)"
                                               @keydown.escape.prevent="cancelEditTag()"
                                               class="bg-transparent text-xs font-mono-tech font-bold text-on-surface py-0.5 px-1 border-none focus:outline-none w-28 sm:w-36">
                                        <button type="button" 
                                                @click="saveEditTag(idx)" 
                                                class="p-1 bg-primary text-white rounded-lg hover:bg-primary-container transition-colors"
                                                title="Guardar cambio">
                                            <span class="material-symbols-outlined text-xs">check</span>
                                        </button>
                                        <button type="button" 
                                                @click="cancelEditTag()" 
                                                class="p-1 text-outline hover:text-on-surface rounded-lg transition-colors"
                                                title="Cancelar">
                                            <span class="material-symbols-outlined text-xs">close</span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                        
                        <div x-show="tagsList.length === 0" class="text-xs text-outline italic py-1">
                            No has seleccionado ninguna etiqueta aún.
                        </div>
                    </div>

                    <!-- Dynamic Smart Suggestions based on text / category -->
                    <div x-show="dynamicSuggestionsNotSelected.length > 0" class="pt-1.5 space-y-1.5 bg-surface-container-low/30 p-3 rounded-2xl border border-surface-container-high/60">
                        <div class="flex items-center gap-1.5 text-[11px] font-semibold text-primary">
                            <span class="material-symbols-outlined text-xs">auto_awesome</span>
                            <span>Sugerencias detectadas para tu propuesta:</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="sugg in dynamicSuggestionsNotSelected" :key="sugg.id">
                                <button type="button" 
                                        @click="toggleTag(sugg.name)" 
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-surface-container-lowest hover:bg-primary-fixed border border-surface-container-high hover:border-primary text-[11px] text-on-surface hover:text-on-primary-fixed-variant transition-colors">
                                    <span class="material-symbols-outlined text-[11px] text-primary">add</span>
                                    <span class="font-mono-tech" x-text="'#' + sugg.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Suggestions from Popular Tags in DB -->
                    <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-outline pt-1">
                        <span>Populares:</span>
                        @foreach($popularTags as $sugg)
                        <button type="button" 
                                @click="if(!tagsList.includes('{{ $sugg->name }}')) tagsList.push('{{ $sugg->name }}')"
                                class="underline hover:text-primary transition-colors">
                            #{{ $sugg->name }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <x-idea-relation-editor :candidates="$relationCandidates" />

            <!-- Attachments Upload Area -->
            <div>
                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                    Archivos o Evidencias Opcionales
                </label>
                <div class="border-2 border-dashed border-surface-container-high rounded-2xl p-6 text-center hover:bg-surface-container-low/50 transition-colors">
                    <span class="material-symbols-outlined text-3xl text-outline mb-2">cloud_upload</span>
                    <p class="text-xs text-on-surface font-semibold">Arrastra archivos o haz clic para seleccionarlos</p>
                    <p class="text-[10px] text-on-surface-variant font-mono-tech mt-1">Soporta PDF, imágenes JPG/PNG o documentos Word (Máx 10 MB)</p>
                    <input type="file" 
                           name="attachments[]" 
                           multiple 
                           accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx"
                           class="mt-3 block w-full text-xs text-outline file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary-fixed file:text-on-primary-fixed-variant hover:file:bg-primary-container hover:file:text-white transition-colors cursor-pointer">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-6 border-t border-surface-container-high flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-xs text-on-surface-variant italic">
                    “Una idea sencilla puede convertirse en una gran innovación.”
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <a href="{{ route('ideas.index') }}" class="w-1/2 sm:w-auto px-5 py-3 text-center text-xs font-semibold text-on-surface-variant hover:text-on-surface transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="w-1/2 sm:w-auto px-6 py-3 bg-gradient-to-r from-primary to-primary-container text-white font-headline font-bold text-sm rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all">
                        Guardar Idea
                    </button>
                </div>
            </div>

        </form>
    </div>

    <!-- Tag Explorer Modal Component -->
    <x-tag-explorer-modal />

</div>
@endsection
