@extends('layouts.app')

@section('title', $currentCommunity->community_name.' - INNOVATEP Ideas')

@section('content')
<div class="space-y-7">
    <section class="rounded-3xl border border-surface-container-high/80 bg-surface-container-lowest p-5 shadow-xs sm:p-7">
        <nav aria-label="Ruta de comunidad" class="flex flex-wrap items-center gap-1.5 text-[11px] font-mono-tech text-outline">
            <a href="{{ route('community', ['nivel' => 'general']) }}" class="hover:text-primary">Comunidad general</a>
            @foreach($communityPath as $pathUnit)
                <span class="material-symbols-outlined text-sm" aria-hidden="true">chevron_right</span>
                @if($pathUnit->is($currentCommunity))
                    <span class="font-bold text-on-surface">{{ $pathUnit->name }}</span>
                @else
                    <a href="{{ route('community', ['nivel' => $pathUnit->id]) }}" class="hover:text-primary">{{ $pathUnit->name }}</a>
                @endif
            @endforeach
        </nav>

        <div class="mt-5 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <div class="mb-2 inline-flex items-center gap-2 text-xs font-bold text-primary">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">corporate_fare</span>
                    {{ $currentCommunity->type_label }}
                </div>
                <h1 class="font-headline text-2xl font-extrabold text-on-surface sm:text-3xl">
                    {{ $currentCommunity->community_name }}
                </h1>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-on-surface-variant">
                    Ideas compartidas para este contexto interno. Aquí se usa el estado de trabajo de cada autor y no se requiere revisión editorial.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('community', ['nivel' => $upUnit?->id ?? 'general']) }}"
                   class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl border border-surface-container-high bg-surface-container-low px-3.5 py-2 text-xs font-bold text-on-surface hover:border-primary/30 hover:text-primary">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_upward</span>
                    Subir un nivel
                </a>

                @if($downUnits->count() === 1)
                    <a href="{{ route('community', ['nivel' => $downUnits->first()->id]) }}"
                       class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl bg-primary px-3.5 py-2 text-xs font-bold text-white hover:bg-primary-container">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_downward</span>
                        Ir a {{ $downUnits->first()->name }}
                    </a>
                @elseif($downUnits->count() > 1)
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" @click.outside="open = false"
                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl bg-primary px-3.5 py-2 text-xs font-bold text-white hover:bg-primary-container"
                                :aria-expanded="open.toString()">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">account_tree</span>
                            Niveles dependientes
                            <span class="material-symbols-outlined text-base" aria-hidden="true">expand_more</span>
                        </button>
                        <div x-show="open" x-transition class="absolute right-0 z-20 mt-2 w-72 rounded-2xl border border-surface-container-high bg-surface-container-lowest p-2 shadow-xl" style="display: none;">
                            @foreach($downUnits as $downUnit)
                                <a href="{{ route('community', ['nivel' => $downUnit->id]) }}" class="block rounded-xl px-3 py-2.5 hover:bg-surface-container-low">
                                    <span class="block text-xs font-bold text-on-surface">{{ $downUnit->name }}</span>
                                    <span class="mt-0.5 block text-[10px] text-on-surface-variant">{{ $downUnit->type_label }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-surface-container-high/80 bg-surface-container-lowest p-4 shadow-xs sm:p-5">
        <x-community-search
            :level="$currentCommunity->id"
            placeholder="Buscar dentro de {{ $currentCommunity->community_name }}..." />
        <p class="mt-2 text-[11px] text-on-surface-variant">Filtra automáticamente sólo las ideas disponibles en este nivel.</p>
    </section>

    <section aria-label="Resumen de la comunidad" class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach([
            ['label' => 'Ideas visibles', 'value' => $totalIdeas, 'icon' => 'lightbulb', 'color' => 'text-primary'],
            ['label' => 'Este mes', 'value' => $thisMonthIdeas, 'icon' => 'calendar_month', 'color' => 'text-secondary'],
            ['label' => 'Completadas', 'value' => $completedIdeas, 'icon' => 'task_alt', 'color' => 'text-tertiary'],
            ['label' => 'Colaboradores', 'value' => $totalParticipants, 'icon' => 'groups', 'color' => 'text-on-surface'],
            ['label' => 'Valoraciones', 'value' => $totalVotes, 'icon' => 'star', 'color' => 'text-primary'],
        ] as $stat)
            <div class="rounded-2xl border border-surface-container-high/70 bg-surface-container-lowest p-4 {{ $loop->last ? 'col-span-2 lg:col-span-1' : '' }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <span class="block text-[10px] font-mono-tech font-bold uppercase text-outline">{{ $stat['label'] }}</span>
                        <span class="mt-1 block font-headline text-2xl font-extrabold {{ $stat['color'] }}">{{ number_format($stat['value']) }}</span>
                    </div>
                    <span class="material-symbols-outlined text-xl {{ $stat['color'] }}" aria-hidden="true">{{ $stat['icon'] }}</span>
                </div>
            </div>
        @endforeach
    </section>

    <section class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-headline text-xl font-bold text-on-surface">Ideas de esta comunidad</h2>
                <p class="mt-1 text-xs text-on-surface-variant">
                    @if(request()->filled('q'))
                        Resultados para “{{ request('q') }}” dentro de este nivel.
                    @else
                        Sólo aparecen ideas madre habilitadas para este nivel.
                    @endif
                </p>
            </div>
            <a href="{{ route('ideas.create') }}" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white hover:bg-primary-container">
                <span class="material-symbols-outlined text-lg" aria-hidden="true">add_circle</span>
                Nueva idea
            </a>
        </div>

        @if($ideas->isNotEmpty())
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach($ideas as $idea)
                    <x-idea-card :idea="$idea" />
                @endforeach
            </div>
            <div class="pt-2">{{ $ideas->links() }}</div>
        @else
            <div class="rounded-3xl border border-dashed border-surface-container-high bg-surface-container-lowest px-6 py-12 text-center">
                <span class="material-symbols-outlined text-4xl text-outline" aria-hidden="true">domain_disabled</span>
                <h3 class="mt-3 font-headline text-base font-bold text-on-surface">{{ request()->filled('q') ? 'No hay coincidencias en esta comunidad' : 'Todavía no hay ideas compartidas aquí' }}</h3>
                <p class="mx-auto mt-1 max-w-md text-xs leading-relaxed text-on-surface-variant">{{ request()->filled('q') ? 'Prueba con otros caracteres, categorías o etiquetas.' : 'Una idea madre aparecerá cuando su autor seleccione esta comunidad como audiencia interna.' }}</p>
            </div>
        @endif
    </section>
</div>
@endsection
