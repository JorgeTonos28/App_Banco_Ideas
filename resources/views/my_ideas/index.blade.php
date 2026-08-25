@extends('layouts.app')

@section('title', 'Mis Ideas - INNOVATEP')

@section('content')
<div class="space-y-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">Mis Ideas</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Organiza tus ideas, decide cuáles compartir en tu perfil y cuáles enviar a la comunidad</p>
        </div>

        <a href="{{ route('ideas.create') }}" 
           class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-primary text-white font-headline font-bold text-sm rounded-xl shadow-md hover:bg-primary-container transition-all">
            <span class="material-symbols-outlined text-xl">add_circle</span>
            <span>Nueva Idea</span>
        </a>
    </div>

    <!-- Personal Stats Row -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high/80 shadow-2xs">
            <span class="text-[11px] font-mono-tech uppercase font-bold text-outline block">Total Creadas</span>
            <span class="font-headline font-extrabold text-2xl text-primary">{{ $totalIdeas }}</span>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high/80 shadow-2xs">
            <span class="text-[11px] font-mono-tech uppercase font-bold text-secondary block">En Revisión</span>
            <span class="font-headline font-extrabold text-2xl text-secondary">{{ $inReviewCount }}</span>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high/80 shadow-2xs">
            <span class="text-[11px] font-mono-tech uppercase font-bold text-primary block">Priorizadas</span>
            <span class="font-headline font-extrabold text-2xl text-primary">{{ $prioritizedCount }}</span>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high/80 shadow-2xs">
            <span class="text-[11px] font-mono-tech uppercase font-bold text-tertiary block">En Desarrollo</span>
            <span class="font-headline font-extrabold text-2xl text-tertiary">{{ $inDevelopmentCount }}</span>
        </div>

        <div class="col-span-2 sm:col-span-1 bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high/80 shadow-2xs">
            <span class="text-[11px] font-mono-tech uppercase font-bold text-emerald-700 block">Implementadas</span>
            <span class="font-headline font-extrabold text-2xl text-emerald-700">{{ $implementedCount }}</span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-surface-container-high overflow-x-auto no-scrollbar pb-px">
        @php
        $tabs = [
            'privadas' => 'Espacio personal',
            'publicadas' => 'Publicadas',
            'borradores' => 'Borradores',
            'implementadas' => 'Implementadas',
            'archivadas' => 'Archivadas / Descartadas',
            'guardadas' => 'Guardadas (Favoritas)',
        ];
        @endphp

        @foreach($tabs as $tabKey => $tabLabel)
        <a href="{{ route('my-ideas.index', ['tab' => $tabKey]) }}" 
           class="px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 whitespace-nowrap transition-colors flex items-center gap-2 {{ $activeTab === $tabKey ? 'border-primary text-primary font-bold' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
            <span>{{ $tabLabel }}</span>
        </a>
        @endforeach
    </div>

    @if($activeTab !== 'guardadas')
    <div class="flex items-center justify-between gap-3">
        <div class="text-xs text-on-surface-variant">
            <span class="font-bold text-on-surface">Vista de estructura</span>
            <span class="hidden sm:inline"> para comprender cómo se agrupan y evolucionan tus ideas.</span>
        </div>
        <div class="inline-flex items-center rounded-xl bg-surface-container p-1 border border-surface-container-high">
            <a href="{{ request()->fullUrlWithQuery(['vista' => 'tree']) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold {{ $viewMode === 'tree' ? 'bg-surface-container-lowest text-primary shadow-2xs' : 'text-on-surface-variant' }}">
                <span class="material-symbols-outlined text-base">account_tree</span>
                Árbol
            </a>
            <a href="{{ request()->fullUrlWithQuery(['vista' => 'cards']) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold {{ $viewMode === 'cards' ? 'bg-surface-container-lowest text-primary shadow-2xs' : 'text-on-surface-variant' }}">
                <span class="material-symbols-outlined text-base">grid_view</span>
                Tarjetas
            </a>
        </div>
    </div>
    @endif

    <!-- Ideas List -->
    @if($viewMode === 'tree' && $activeTab !== 'guardadas' && $treeRoots->isNotEmpty())
    <div class="space-y-4" x-data="ideaTree(@js($treeSearchTerms->values()->all()))">
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-outline" aria-hidden="true">search</span>
            <input type="search" x-model.debounce.60ms="query" placeholder="Buscar dentro de este árbol sin importar espacios..." class="w-full rounded-xl border border-surface-container-high bg-surface-container-lowest py-3 pl-10 pr-10 text-sm text-on-surface placeholder:text-outline focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
            <button x-show="query.length" type="button" @click="query = ''" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1 text-outline hover:text-on-surface" aria-label="Limpiar búsqueda">
                <span class="material-symbols-outlined text-base" aria-hidden="true">close</span>
            </button>
        </div>

        <div class="space-y-4">
            @foreach($treeRoots as $rootIdea)
                <x-idea-tree-node :node="$rootIdea" :tree-by-parent="$treeByParent" :search-terms="$treeSearchTerms" />
            @endforeach
        </div>

        <div x-show="!hasMatches()" class="rounded-2xl border border-dashed border-surface-container-high bg-surface-container-lowest p-8 text-center">
            <span class="material-symbols-outlined text-3xl text-outline" aria-hidden="true">search_off</span>
            <p class="mt-2 text-xs font-bold text-on-surface">No hay coincidencias en este árbol</p>
        </div>
    </div>
    @elseif($ideas->isNotEmpty())
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($ideas as $idea)
        <div class="bg-surface-container-lowest rounded-2xl p-6 border border-surface-container-high/80 shadow-2xs flex flex-col justify-between group">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-primary font-mono-tech">{{ $idea->category?->name ?: 'General' }}</span>
                    @if($idea->isPublished())
                        <x-status-badge :status="$idea->status" />
                    @else
                        <span class="px-2 py-1 rounded-lg bg-surface-container text-[10px] font-bold text-on-surface-variant">{{ $idea->workspace_status_label }}</span>
                    @endif
                </div>

                <a href="{{ route('ideas.show', $idea->slug) }}" class="block group-hover:text-primary transition-colors">
                    <h3 class="font-headline font-bold text-base text-on-surface line-clamp-2 mb-2">{{ $idea->title }}</h3>
                </a>

                <p class="text-xs text-on-surface-variant line-clamp-3 mb-4">{{ $idea->summary }}</p>
            </div>

            <div class="pt-4 border-t border-surface-container-high/60 flex items-center justify-between mt-auto">
                <div class="flex items-center gap-2 text-xs font-mono-tech text-outline">
                    <span>★ {{ number_format($idea->average_rating, 1) }}</span>
                    <span>•</span>
                    <span>{{ $idea->votes_count }} votos</span>
                    <span>•</span>
                    <span>{{ $idea->isPublished() ? 'Comunidad' : $idea->access_scope_label }}</span>
                </div>

                <div class="flex items-center gap-2">
                    @can('update', $idea)
                    <a href="{{ route('ideas.edit', $idea->id) }}" class="p-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface transition-colors" title="Editar idea">
                        <span class="material-symbols-outlined text-base">edit</span>
                    </a>
                    @endcan
                    @can('delete', $idea)
                    <form action="{{ route('ideas.destroy', $idea->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta propuesta?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-1.5 rounded-lg bg-error-container/40 hover:bg-error-container text-error transition-colors" title="Eliminar idea">
                            <span class="material-symbols-outlined text-base">delete</span>
                        </button>
                    </form>
                    @endcan
                    <a href="{{ route('ideas.show', $idea->slug) }}" class="p-1.5 rounded-lg bg-primary-fixed text-primary hover:bg-primary-container hover:text-white transition-colors" title="Ver detalle">
                        <span class="material-symbols-outlined text-base">visibility</span>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($viewMode !== 'tree' || $activeTab === 'guardadas')
    <div class="pt-4">
        {{ $ideas->links() }}
    </div>
    @endif
    @else
    <!-- Empty State -->
    <div class="bg-surface-container-lowest rounded-3xl p-12 text-center border border-surface-container-high max-w-md mx-auto my-8">
        <div class="w-16 h-16 rounded-2xl bg-surface-container flex items-center justify-center mx-auto mb-4 text-outline">
            <span class="material-symbols-outlined text-3xl">folder_open</span>
        </div>
        <h3 class="font-headline font-bold text-base text-on-surface">No tienes ideas en esta pestaña</h3>
        <p class="text-xs text-on-surface-variant mt-1">
            Empieza con algo pequeño: un proceso que optimizar o una idea pedagógica.
        </p>
        <a href="{{ route('ideas.create') }}" class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white text-xs font-bold rounded-xl shadow-xs hover:bg-primary-container">
            <span class="material-symbols-outlined text-base">add</span>
            <span>Compartir mi primera idea</span>
        </a>
    </div>
    @endif

</div>
@endsection
