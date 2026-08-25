@extends('layouts.app')

@section('title', 'Explorar Ideas - INNOVATEP')

@section('content')
<div class="space-y-6" x-data="{ filtersOpen: false }">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">Explorar Ideas</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Explora ideas principales y navega sus subideas, dimensiones y conexiones</p>
        </div>

        <a href="{{ route('ideas.create') }}" 
           class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-gradient-to-r from-primary to-primary-container text-white font-headline font-bold text-sm rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all">
            <span class="material-symbols-outlined text-xl">add_circle</span>
            <span>Nueva Idea</span>
        </a>
    </div>

    <!-- Filter and Search Toolbar -->
    <div class="bg-surface-container-lowest rounded-2xl p-4 sm:p-5 border border-surface-container-high/80 shadow-2xs space-y-4">
        <form method="GET" action="{{ route('ideas.index') }}" class="space-y-4">
            <div class="flex flex-col md:flex-row items-center gap-3">
                <!-- Search input -->
                <div class="relative flex-1 w-full">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
                    <input type="text" 
                           name="q" 
                           value="{{ request('q') }}" 
                           placeholder="Buscar por título, problema o autor..." 
                           class="w-full bg-surface-container-low text-on-surface text-sm rounded-xl py-2.5 pl-10 pr-4 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <!-- Sort dropdown -->
                <div class="w-full md:w-56">
                    <select name="orden" onchange="this.form.submit()" class="w-full bg-surface-container-low text-on-surface text-sm rounded-xl py-2.5 px-3 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="recientes" {{ request('orden') == 'recientes' ? 'selected' : '' }}>Más recientes</option>
                        <option value="mas_votadas" {{ request('orden') == 'mas_votadas' ? 'selected' : '' }}>Más votadas</option>
                        <option value="tendencia" {{ request('orden') == 'tendencia' ? 'selected' : '' }}>En tendencia</option>
                        <option value="mejor_valoradas" {{ request('orden') == 'mejor_valoradas' ? 'selected' : '' }}>Mejor puntuadas</option>
                        <option value="mas_comentadas" {{ request('orden') == 'mas_comentadas' ? 'selected' : '' }}>Más comentadas</option>
                        <option value="implementadas" {{ request('orden') == 'implementadas' ? 'selected' : '' }}>Solo implementadas</option>
                    </select>
                </div>

                <!-- Filter Toggle Button -->
                <button type="button" 
                        @click="filtersOpen = !filtersOpen" 
                        class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-xl text-sm font-medium transition-colors">
                    <span class="material-symbols-outlined text-lg">filter_list</span>
                    <span>Filtros</span>
                    @if(request('categoria') || request('estado') || request('etiqueta') || request('departamento') || request('facetas'))
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                    @endif
                </button>
            </div>

            <!-- Expanded Filters Panel -->
            <div x-show="filtersOpen" x-transition class="pt-4 border-t border-surface-container-high grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Category Filter -->
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-1.5 font-mono-tech uppercase">Categoría</label>
                    <select name="categoria" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                        <option value="">Todas las categorías</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->slug }}" {{ request('categoria') == $cat->slug ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if($categoryDimensions->where('is_primary', false)->isNotEmpty())
                <div class="sm:col-span-2 lg:col-span-4 pt-3 border-t border-surface-container-high/70">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="material-symbols-outlined text-primary text-lg">tune</span>
                        <span class="text-xs font-bold text-on-surface font-mono-tech uppercase">Dimensiones de clasificación</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($categoryDimensions->where('is_primary', false) as $dimension)
                        <fieldset>
                            <legend class="text-xs font-bold text-on-surface mb-2">{{ $dimension->name }}</legend>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($dimension->categories as $term)
                                <label class="cursor-pointer">
                                    <input type="checkbox"
                                           name="facetas[{{ $dimension->slug }}][]"
                                           value="{{ $term->slug }}"
                                           class="peer sr-only"
                                           {{ in_array($term->slug, request("facetas.{$dimension->slug}", []), true) ? 'checked' : '' }}>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-surface-container-high bg-surface-container-low text-[11px] text-on-surface-variant peer-checked:bg-primary-fixed peer-checked:border-primary peer-checked:text-primary peer-checked:font-bold">
                                        {{ $term->name }}
                                        <span class="font-mono-tech text-[9px] opacity-70">{{ $term->community_ideas_count }}</span>
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </fieldset>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Status Filter -->
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-1.5 font-mono-tech uppercase">Estado</label>
                    <select name="estado" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                        <option value="">Todos los estados</option>
                        <option value="nueva" {{ request('estado') == 'nueva' ? 'selected' : '' }}>💡 Nueva</option>
                        <option value="en_revision" {{ request('estado') == 'en_revision' ? 'selected' : '' }}>👀 En revisión</option>
                        <option value="priorizada" {{ request('estado') == 'priorizada' ? 'selected' : '' }}>⭐ Priorizada</option>
                        <option value="en_desarrollo" {{ request('estado') == 'en_desarrollo' ? 'selected' : '' }}>🧪 En desarrollo</option>
                        <option value="implementada" {{ request('estado') == 'implementada' ? 'selected' : '' }}>🚀 Implementada</option>
                    </select>
                </div>

                <!-- Tag Filter -->
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-1.5 font-mono-tech uppercase">Etiqueta</label>
                    <select name="etiqueta" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                        <option value="">Todas las etiquetas</option>
                        @foreach($tags as $t)
                        <option value="{{ $t->slug }}" {{ request('etiqueta') == $t->slug ? 'selected' : '' }}>#{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Area / Department Filter -->
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-1.5 font-mono-tech uppercase">Área / Departamento</label>
                    <select name="departamento" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                        <option value="">Todas las áreas</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ request('departamento') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2 lg:col-span-4 flex items-center justify-end gap-2 pt-2">
                    <a href="{{ route('ideas.index') }}" class="px-4 py-2 text-xs font-medium text-on-surface-variant hover:text-primary">Limpiar filtros</a>
                    <button type="submit" class="px-5 py-2 bg-primary text-white text-xs font-bold rounded-xl shadow-xs hover:bg-primary-container">Aplicar Filtros</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Ideas Grid -->
    @if($ideas->isNotEmpty())
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($ideas as $idea)
            <x-idea-card :idea="$idea" />
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="pt-6">
        {{ $ideas->links() }}
    </div>
    @else
    <!-- Empty State -->
    <div class="bg-surface-container-lowest rounded-3xl p-12 text-center border border-surface-container-high max-w-lg mx-auto my-8">
        <div class="w-16 h-16 rounded-2xl bg-primary-fixed text-primary flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-3xl">lightbulb</span>
        </div>
        <h3 class="font-headline font-bold text-lg text-on-surface">No se encontraron ideas</h3>
        <p class="text-xs sm:text-sm text-on-surface-variant mt-1.5 leading-relaxed">
            No hay propuestas que coincidan con tus criterios de búsqueda o filtros seleccionados.
        </p>
        <div class="mt-6 flex justify-center gap-3">
            <a href="{{ route('ideas.index') }}" class="px-4 py-2 bg-surface-container text-xs font-semibold rounded-xl text-on-surface hover:bg-surface-container-high">
                Ver todas las ideas
            </a>
            <a href="{{ route('ideas.create') }}" class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-container">
                Crear una idea
            </a>
        </div>
    </div>
    @endif

</div>
@endsection
