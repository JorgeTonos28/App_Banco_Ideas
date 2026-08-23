@extends('layouts.app')

@section('title', 'Editar Idea - INNOVATEP')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" 
     x-data="{ 
        tagsList: {{ json_encode(old('tags', $selectedTags)) }}, 
        tagInput: '',
        openTagModal: false,
        modalSearch: '',
        modalTab: 'alphabetical',
        selectedCategoryFilter: null,
        selectedLetter: 'ALL',
        selectedCategoryId: '{{ old('category_id', $idea->category_id) }}',
        titleText: '{{ old('title', $idea->title) }}',
        descriptionText: '{{ old('description', $idea->description) }}',
        allTags: {{ json_encode($allTags) }},
        categories: {{ json_encode($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'icon' => $c->icon, 'color' => $c->color])) }},

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
            let list = this.allTags;
            if (this.modalSearch.trim().length > 0) {
                const q = this.modalSearch.toLowerCase().trim();
                return list.filter(t => t.name.toLowerCase().includes(q));
            }
            if (this.modalTab === 'categories') {
                if (this.selectedCategoryFilter) {
                    return list.filter(t => t.category_ids.includes(parseInt(this.selectedCategoryFilter)));
                }
                return list;
            }
            if (this.modalTab === 'popular') {
                return [...list].sort((a, b) => b.ideas_count - a.ideas_count);
            }
            if (this.modalTab === 'suggested') {
                return this.suggestedTags;
            }
            if (this.selectedLetter !== 'ALL') {
                return list.filter(t => t.name.toUpperCase().startsWith(this.selectedLetter));
            }
            return list;
        },

        get alphabeticalGroups() {
            const groups = {};
            const tags = this.filteredTags;
            tags.forEach(t => {
                const letter = (t.name[0] || '#').toUpperCase();
                if (!groups[letter]) groups[letter] = [];
                groups[letter].push(t);
            });
            return groups;
        },

        get availableLetters() {
            const letters = new Set();
            this.allTags.forEach(t => {
                if (t.name) letters.add(t.name[0].toUpperCase());
            });
            return Array.from(letters).sort();
        },

        get suggestedTags() {
            const suggestions = [];
            const seen = new Set();

            // 1. By selected category
            if (this.selectedCategoryId) {
                const catId = parseInt(this.selectedCategoryId);
                this.allTags.forEach(t => {
                    if (t.category_ids.includes(catId) && !seen.has(t.name)) {
                        seen.add(t.name);
                        suggestions.push(t);
                    }
                });
            }

            // 2. Text keyword match from title & description
            const text = ((this.titleText || '') + ' ' + (this.descriptionText || '')).toLowerCase();
            if (text.trim().length >= 3) {
                this.allTags.forEach(t => {
                    const tName = t.name.toLowerCase();
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
            const clean = (name || this.tagInput || this.modalSearch).trim();
            if (clean && !this.tagsList.includes(clean)) {
                this.tagsList.push(clean);
                if (!this.allTags.some(t => t.name.toLowerCase() === clean.toLowerCase())) {
                    this.allTags.push({
                        id: Date.now(),
                        name: clean,
                        slug: clean.toLowerCase().replace(/\s+/g, '-'),
                        ideas_count: 0,
                        category_ids: this.selectedCategoryId ? [parseInt(this.selectedCategoryId)] : []
                    });
                }
                this.tagInput = '';
                this.modalSearch = '';
            }
        }
     }">

    <!-- Header Section -->
    <div class="text-center sm:text-left">
        <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">Editar Idea</h1>
        <p class="text-xs sm:text-sm text-on-surface-variant mt-1.5 leading-relaxed">
            Actualiza los detalles de tu propuesta para mejorar la comprensión de la comunidad.
        </p>
    </div>

    <!-- Main Edit Form Card -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-10 border border-surface-container-high/80 shadow-xs">
        
        @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-error-container/60 border border-error/30 text-error text-xs font-medium space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">error</span>
                <span>Por favor revisa los errores:</span>
            </div>
            <ul class="list-disc list-inside pl-2 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('ideas.update', $idea->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Title -->
            <div>
                <label for="title" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                    Título de la idea <span class="text-error">*</span>
                </label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       x-model="titleText"
                       value="{{ old('title', $idea->title) }}" 
                       required 
                       class="w-full bg-surface-container-low text-on-surface text-sm sm:text-base rounded-2xl py-3 px-4 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                    Describe tu idea <span class="text-error">*</span>
                </label>
                <textarea id="description" 
                          name="description" 
                          x-model="descriptionText"
                          rows="5" 
                          required 
                          class="w-full bg-surface-container-low text-on-surface text-sm rounded-2xl p-4 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-y">{{ old('description', $idea->description) }}</textarea>
            </div>

            <!-- Problem / Opportunity -->
            <div>
                <label for="problem_opportunity" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                    ¿Qué problema u oportunidad busca resolver?
                </label>
                <textarea id="problem_opportunity" 
                          name="problem_opportunity" 
                          rows="3" 
                          class="w-full bg-surface-container-low text-on-surface text-sm rounded-2xl p-4 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-y">{{ old('problem_opportunity', $idea->problem_opportunity) }}</textarea>
            </div>

            <!-- Category & Visibility -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="category_id" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                        Categoría <span class="text-error">*</span>
                    </label>
                    <select id="category_id" 
                            name="category_id" 
                            x-model="selectedCategoryId"
                            required 
                            class="w-full bg-surface-container-low text-on-surface text-sm rounded-2xl p-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $idea->category_id) == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="visibility" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                        Visibilidad <span class="text-error">*</span>
                    </label>
                    <select id="visibility" 
                            name="visibility" 
                            class="w-full bg-surface-container-low text-on-surface text-sm rounded-2xl p-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="public" {{ old('visibility', $idea->visibility) == 'public' ? 'selected' : '' }}>Visible para toda la comunidad</option>
                        <option value="draft" {{ old('visibility', $idea->visibility) == 'draft' ? 'selected' : '' }}>Guardar como borrador privado</option>
                    </select>
                </div>
            </div>

            <!-- Tags Input with Chips & Modal Explorer -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech">
                        Etiquetas
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
                               placeholder="Escribe una etiqueta y presiona Enter o Agregar"
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

            <!-- Existing Attachments & Deletion -->
            @if($idea->attachments->isNotEmpty())
            <div>
                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                    Archivos actuales (Marca los que deseas eliminar)
                </label>
                <div class="space-y-2">
                    @foreach($idea->attachments as $att)
                    <label class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low border border-surface-container-high cursor-pointer hover:bg-surface-container">
                        <div class="flex items-center gap-2.5 text-xs text-on-surface">
                            <span class="material-symbols-outlined text-base text-primary">description</span>
                            <span>{{ $att->file_name }} ({{ $att->formatted_size }})</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-error font-medium">
                            <input type="checkbox" name="delete_attachments[]" value="{{ $att->id }}" class="rounded text-error focus:ring-error">
                            <span>Eliminar</span>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- New Attachments -->
            <div>
                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                    Agregar nuevos archivos
                </label>
                <input type="file" 
                       name="attachments[]" 
                       multiple 
                       accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
                       class="block w-full text-xs text-outline file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary-fixed file:text-on-primary-fixed-variant hover:file:bg-primary-container hover:file:text-white transition-colors cursor-pointer">
            </div>

            <!-- Action Buttons -->
            <div class="pt-6 border-t border-surface-container-high flex items-center justify-between">
                <a href="{{ route('ideas.show', $idea->slug) }}" class="px-5 py-3 text-xs font-semibold text-on-surface-variant hover:text-on-surface">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-primary text-white font-headline font-bold text-sm rounded-xl shadow-md hover:bg-primary-container transition-all">
                    Guardar Cambios
                </button>
            </div>

        </form>
    </div>

    <!-- Tag Explorer Modal Component -->
    <x-tag-explorer-modal />

</div>
@endsection
