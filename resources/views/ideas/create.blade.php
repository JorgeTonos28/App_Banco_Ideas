@extends('layouts.app')

@section('title', 'Compartir una Idea - INNOVATEP')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="{ tagsList: [], tagInput: '' }">

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
                            required 
                            class="w-full bg-surface-container-low text-on-surface text-sm rounded-2xl p-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Selecciona una categoría</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
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
                        <option value="draft" {{ old('visibility') == 'draft' ? 'selected' : '' }}>Guardar como borrador privado</option>
                    </select>
                </div>
            </div>

            <!-- Tags Input with Chips -->
            <div>
                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-2">
                    Etiquetas o Palabras Clave
                </label>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <input type="text" 
                               x-model="tagInput" 
                               @keydown.enter.prevent="if(tagInput.trim() && !tagsList.includes(tagInput.trim())) { tagsList.push(tagInput.trim()); tagInput = ''; }"
                               placeholder="Escribe una etiqueta y presiona Enter o Agregar"
                               class="flex-1 bg-surface-container-low text-on-surface text-xs rounded-xl py-2.5 px-3.5 border border-surface-container-high focus:outline-none focus:ring-1 focus:ring-primary">
                        <button type="button" 
                                @click="if(tagInput.trim() && !tagsList.includes(tagInput.trim())) { tagsList.push(tagInput.trim()); tagInput = ''; }"
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
                    </div>

                    <!-- Suggestions from DB -->
                    <div class="flex flex-wrap gap-1.5 text-[11px] text-outline pt-1">
                        <span>Sugerencias:</span>
                        @foreach($tags->take(6) as $sugg)
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

</div>
@endsection
