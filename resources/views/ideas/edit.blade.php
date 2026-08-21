@extends('layouts.app')

@section('title', 'Editar Idea - INNOVATEP')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="{ tagsList: {{ json_encode($selectedTags) }}, tagInput: '' }">

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

            <!-- Tags -->
            <div>
                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                    Etiquetas
                </label>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <input type="text" 
                               x-model="tagInput" 
                               @keydown.enter.prevent="if(tagInput.trim() && !tagsList.includes(tagInput.trim())) { tagsList.push(tagInput.trim()); tagInput = ''; }"
                               placeholder="Escribe una etiqueta y presiona Enter o Agregar"
                               class="flex-1 bg-surface-container-low text-on-surface text-xs rounded-xl py-2.5 px-3.5 border border-surface-container-high">
                        <button type="button" 
                                @click="if(tagInput.trim() && !tagsList.includes(tagInput.trim())) { tagsList.push(tagInput.trim()); tagInput = ''; }"
                                class="px-4 py-2.5 bg-surface-container hover:bg-surface-container-high text-xs font-semibold text-on-surface rounded-xl">
                            Agregar
                        </button>
                    </div>

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

</div>
@endsection
