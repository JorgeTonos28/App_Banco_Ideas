@extends('layouts.app')

@section('title', 'Compartir una Idea - INNOVATEP')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" 
     x-data="{ 
        tagsList: {{ json_encode(array_values(old('tags', []))) }}, 
        tagInput: '',
        openTagModal: false,
        modalSearch: '',
        modalTab: 'alphabetical',
        selectedCategoryFilter: null,
        selectedLetter: 'ALL',
        selectedCategoryId: '{{ old('category_id', '') }}',
        titleText: '',
        descriptionText: '',
        allTags: {{ json_encode($allTags->values()) }},
        categories: {{ json_encode($categories->map(fn($c) => ['id' => (int)$c->id, 'name' => (string)$c->name, 'icon' => (string)($c->icon ?? 'folder'), 'color' => (string)($c->color ?? '#003e6f')])->values()) }},

        init() {
            if (this.$refs.titleInput) this.titleText = this.$refs.titleInput.value || '';
            if (this.$refs.descriptionInput) this.descriptionText = this.$refs.descriptionInput.value || '';
            const catEl = document.getElementById('category_id');
            if (catEl && catEl.value) this.selectedCategoryId = catEl.value;
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

        get filteredTags() {
            let list = this.allTags || [];
            if (this.modalSearch && this.modalSearch.trim().length > 0) {
                const q = this.modalSearch.toLowerCase().trim();
                return list.filter(t => t.name.toLowerCase().includes(q));
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
                if (!this.tagsList.includes(clean)) {
                    this.tagsList.push(clean);
                    if (!this.allTags.some(t => (t.name || '').toLowerCase() === clean.toLowerCase())) {
                        this.allTags.push({
                            id: Date.now() + Math.floor(Math.random() * 1000),
                            name: clean,
                            slug: clean.toLowerCase().replace(/\s+/g, '-'),
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

            <!-- Category -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
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

                <!-- Visibility -->
                <div>
                    <label for="visibility" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                        Visibilidad <span class="text-error">*</span>
                    </label>
                    <select id="visibility" 
                            name="visibility" 
                            class="w-full bg-surface-container-low text-on-surface text-sm rounded-2xl p-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="public" {{ old('visibility', 'public') == 'public' ? 'selected' : '' }}>Visible para toda la comunidad</option>
                        <option value="draft" {{ old('visibility', 'draft') == 'draft' ? 'selected' : '' }}>Guardar como borrador privado</option>
                    </select>
                </div>
            </div>

            <!-- Tags Input with Chips & Modal Explorer -->
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

                    <!-- Selected Tags Chips List -->
                    <div class="flex flex-wrap gap-2 pt-1 min-h-[32px]">
                        <template x-for="(tag, idx) in tagsList" :key="idx">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-primary-fixed text-on-primary-fixed-variant text-xs font-mono-tech">
                                <span x-text="'#' + tag"></span>
                                <input type="hidden" name="tags[]" :value="tag">
                                <button type="button" @click="tagsList.splice(idx, 1)" class="hover:text-error">
                                    <span class="material-symbols-outlined text-xs">close</span>
                                </button>
                            </span>
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
                        Publicar Idea
                    </button>
                </div>
            </div>

        </form>
    </div>

    <!-- Tag Explorer Modal Component -->
    <x-tag-explorer-modal />

</div>
@endsection
