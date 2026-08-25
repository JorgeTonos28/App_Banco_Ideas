@extends('layouts.app')

@section('title', 'Comunidad - Feed de Innovación INNOVATEP')

@section('content')
<div class="space-y-8">

    <!-- Hero / Greeting Card -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-surface-container-low via-surface-container to-surface-container-low p-6 sm:p-10 border border-surface-container-high/60 shadow-xs">
        <!-- Ambient Glow Blob -->
        <div class="absolute -top-16 -right-16 w-64 h-64 bg-primary-fixed-dim/40 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-16 w-64 h-64 bg-secondary-fixed/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="max-w-2xl">
                <h1 class="font-headline font-extrabold text-2xl sm:text-4xl text-on-surface flex items-center gap-2">
                    <span>Hola, {{ auth()->check() ? auth()->user()->name : 'Colaborador' }}</span>
                    <span class="inline-block origin-bottom-right animate-wave">👋</span>
                </h1>
                <p class="text-sm sm:text-base text-on-surface-variant mt-2 leading-relaxed">
                    ¿Tienes una idea que podría optimizar un proceso, aula o taller en INFOTEP? Compártela con la comunidad y hazla evolucionar.
                </p>
            </div>

            <a href="{{ route('ideas.create') }}" 
               class="shrink-0 inline-flex items-center gap-2.5 px-6 py-3.5 bg-gradient-to-r from-primary to-primary-container text-white font-headline font-bold text-sm sm:text-base rounded-2xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 transition-all group">
                <span class="material-symbols-outlined text-2xl group-hover:rotate-90 transition-transform">add_circle</span>
                <span>Compartir una idea</span>
            </a>
        </div>
    </div>

    <nav aria-label="Niveles de comunidad" class="flex flex-col gap-3 rounded-2xl border border-surface-container-high/70 bg-surface-container-lowest p-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-fixed text-primary">
                <span class="material-symbols-outlined" aria-hidden="true">public</span>
            </div>
            <div>
                <span class="block text-xs font-bold text-on-surface">Comunidad general de INFOTEP</span>
                <span class="block text-[11px] text-on-surface-variant">Ideas aprobadas para todas las regionales y sedes.</span>
            </div>
        </div>
        @if($downUnits->isNotEmpty())
            <a href="{{ route('community', ['nivel' => $downUnits->first()->id]) }}"
               class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-xl bg-primary-fixed px-3.5 py-2 text-xs font-bold text-primary hover:bg-primary hover:text-white">
                <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_downward</span>
                Entrar a mi estructura
            </a>
        @endif
    </nav>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Stat 1: Publicadas -->
        <div class="bg-surface-container-lowest rounded-2xl p-4 sm:p-5 border border-surface-container-high/60 shadow-2xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-mono-tech uppercase font-bold text-outline tracking-wider block">Publicadas</span>
                <span class="font-headline font-extrabold text-xl sm:text-2xl text-primary">{{ number_format($totalIdeas) }}</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-primary-fixed flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-xl">lightbulb</span>
            </div>
        </div>

        <!-- Stat 2: Este Mes -->
        <div class="bg-surface-container-lowest rounded-2xl p-4 sm:p-5 border border-surface-container-high/60 shadow-2xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-mono-tech uppercase font-bold text-outline tracking-wider block">Este Mes</span>
                <span class="font-headline font-extrabold text-xl sm:text-2xl text-secondary-container">{{ number_format($thisMonthIdeas) }}</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-secondary-fixed flex items-center justify-center text-secondary">
                <span class="material-symbols-outlined text-xl">trending_up</span>
            </div>
        </div>

        <!-- Stat 3: Implementadas -->
        <div class="bg-surface-container-lowest rounded-2xl p-4 sm:p-5 border border-surface-container-high/60 shadow-2xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-mono-tech uppercase font-bold text-outline tracking-wider block">Implementadas</span>
                <span class="font-headline font-extrabold text-xl sm:text-2xl text-tertiary">{{ number_format($implementedIdeas) }}</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-tertiary-fixed flex items-center justify-center text-tertiary">
                <span class="material-symbols-outlined text-xl">rocket_launch</span>
            </div>
        </div>

        <!-- Stat 4: Colaboradores -->
        <div class="bg-surface-container-lowest rounded-2xl p-4 sm:p-5 border border-surface-container-high/60 shadow-2xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-mono-tech uppercase font-bold text-outline tracking-wider block">Participantes</span>
                <span class="font-headline font-extrabold text-xl sm:text-2xl text-on-surface">{{ number_format($totalParticipants) }}</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-surface-container-high flex items-center justify-center text-on-surface-variant">
                <span class="material-symbols-outlined text-xl">group</span>
            </div>
        </div>

        <!-- Stat 5: Votos -->
        <div class="col-span-2 lg:col-span-1 bg-surface-container-lowest rounded-2xl p-4 sm:p-5 border border-surface-container-high/60 shadow-2xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-mono-tech uppercase font-bold text-outline tracking-wider block">Votos Totales</span>
                <span class="font-headline font-extrabold text-xl sm:text-2xl text-primary">{{ number_format($totalVotes) }}</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-primary-fixed flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-xl">thumb_up</span>
            </div>
        </div>
    </div>

    <!-- Popular Categories Chips -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="font-headline font-bold text-lg text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">category</span>
                <span>Categorías Populares</span>
            </h2>
            <a href="{{ route('ideas.index') }}" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                <span>Ver todas</span>
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('ideas.index') }}" 
               class="px-4 py-2 rounded-full bg-primary text-white text-xs font-semibold shadow-xs">
                Todas las ideas
            </a>
            @foreach($popularCategories as $cat)
            <a href="{{ route('ideas.index', ['categoria' => $cat->slug]) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-surface-container-lowest hover:bg-surface-container-low text-on-surface text-xs font-medium border border-surface-container-high transition-colors shadow-2xs group">
                <span class="material-symbols-outlined text-sm text-outline group-hover:text-primary transition-colors">{{ $cat->icon }}</span>
                <span>{{ $cat->name }}</span>
                <span class="px-1.5 py-0.5 rounded-full bg-surface-container text-[10px] font-mono-tech font-bold text-on-surface-variant">{{ $cat->ideas_count }}</span>
            </a>
            @endforeach
        </div>
    </div>

    <!-- Main 2-Column Grid: Left (Feed & Featured), Right (Trending & Innovators) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left Column: Featured & Latest (8 cols) -->
        <div class="lg:col-span-8 space-y-8">
            
            <!-- Featured Ideas Section -->
            @if($featuredIdeas->isNotEmpty())
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-headline font-bold text-xl text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary text-2xl" style="font-variation-settings: 'FILL' 1;">stars</span>
                        <span>Ideas Destacadas</span>
                    </h2>
                    <a href="{{ route('ideas.index', ['orden' => 'mejor_valoradas']) }}" class="text-xs font-semibold text-primary hover:underline">
                        Ver todas
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($featuredIdeas as $idea)
                        <x-idea-card :idea="$idea" />
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Latest Ideas Feed Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-headline font-bold text-xl text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-2xl">schedule</span>
                        <span>Últimas Ideas Publicadas</span>
                    </h2>
                    <a href="{{ route('ideas.index', ['orden' => 'recientes']) }}" class="text-xs font-semibold text-primary hover:underline">
                        Explorar feed
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($latestIdeas as $idea)
                        <x-idea-card :idea="$idea" />
                    @endforeach
                </div>
            </div>

        </div>

        <!-- Right Column: Trending & Top Innovators (4 cols) -->
        <div class="lg:col-span-4 space-y-8">
            
            <!-- Trending Ideas Widget -->
            <div class="bg-surface-container-lowest rounded-3xl p-6 border border-surface-container-high/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-headline font-bold text-base text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-orange-600 text-xl" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                        <span>En Tendencia</span>
                    </h2>
                    <span class="text-[11px] font-mono-tech text-outline">Actividad reciente</span>
                </div>

                <div class="divide-y divide-surface-container-high/60 space-y-3 pt-1">
                    @foreach($trendingIdeas as $index => $idea)
                    <div class="pt-3 first:pt-0 flex items-center gap-3 group">
                        <div class="w-8 h-8 rounded-xl bg-surface-container flex items-center justify-center font-headline font-bold text-xs text-primary group-hover:bg-primary group-hover:text-white transition-colors shrink-0">
                            #{{ $index + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('ideas.show', $idea->slug) }}" class="text-xs font-bold text-on-surface hover:text-primary transition-colors block truncate">
                                {{ $idea->title }}
                            </a>
                            <div class="flex items-center gap-2 text-[10px] text-on-surface-variant font-mono-tech mt-0.5">
                                <span>{{ $idea->user->name }}</span>
                                <span>•</span>
                                <span class="text-primary font-semibold">Score: {{ $idea->innovation_score }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="pt-2 border-t border-surface-container-high/60 text-center">
                    <a href="{{ route('ranking.index') }}" class="text-xs font-semibold text-primary hover:underline">
                        Ver Ranking Completo
                    </a>
                </div>
            </div>

            <!-- Top Innovators Widget -->
            <div class="bg-surface-container-lowest rounded-3xl p-6 border border-surface-container-high/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-headline font-bold text-base text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary text-xl" style="font-variation-settings: 'FILL' 1;">emoji_events</span>
                        <span>Top Innovadores</span>
                    </h2>
                </div>

                <div class="space-y-3">
                    @foreach($topInnovators as $innovator)
                    <a href="{{ route('profile.show', $innovator->id) }}" class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-surface-container-low transition-colors group">
                        <div class="flex items-center gap-3 min-w-0">
                            <img src="{{ $innovator->avatar_url }}" alt="{{ $innovator->name }}" class="w-9 h-9 rounded-full object-cover ring-1 ring-white shadow-2xs">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-on-surface group-hover:text-primary transition-colors truncate">{{ $innovator->name }}</p>
                                <p class="text-[10px] text-on-surface-variant truncate">{{ $innovator->department ?: 'INFOTEP' }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-mono-tech font-bold text-primary block">{{ $innovator->ideas_count }}</span>
                            <span class="text-[9px] font-mono-tech text-outline uppercase">ideas</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
