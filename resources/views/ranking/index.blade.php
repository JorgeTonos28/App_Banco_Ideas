@extends('layouts.app')

@section('title', 'Ranking de Ideas - INNOVATEP')

@section('content')
<div class="space-y-8">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary-container text-3xl" style="font-variation-settings: 'FILL' 1;">leaderboard</span>
                <span>Ranking de Ideas</span>
            </h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">
                Descubre las propuestas con mayor impacto y valoración por la comunidad INNOVATEP
            </p>
        </div>

        <!-- Time Period Pills -->
        <div class="flex items-center gap-1.5 p-1 bg-surface-container rounded-2xl border border-surface-container-high self-start md:self-auto">
            @foreach(['historico' => 'Histórico', 'mes' => 'Este Mes', 'semana' => 'Esta Semana', 'anio' => 'Este Año'] as $key => $label)
            <a href="{{ route('ranking.index', array_merge(request()->query(), ['periodo' => $key])) }}" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $periodo === $key ? 'bg-primary text-white shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    <!-- Filters Bar -->
    <form method="GET" action="{{ route('ranking.index') }}" class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high flex flex-wrap items-center gap-3">
        <input type="hidden" name="periodo" value="{{ $periodo }}">
        
        <div class="w-full sm:w-auto flex-1 min-w-[200px]">
            <select name="categoria" onchange="this.form.submit()" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                <option value="">Todas las categorías</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->slug }}" {{ request('categoria') == $cat->slug ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-auto flex-1 min-w-[200px]">
            <select name="departamento" onchange="this.form.submit()" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                <option value="">Todas las áreas / departamentos</option>
                @foreach($departments as $dept)
                <option value="{{ $dept }}" {{ request('departamento') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-auto flex-1 min-w-[180px]">
            <select name="estado" onchange="this.form.submit()" class="w-full bg-surface-container-low text-xs rounded-xl p-2.5 border border-surface-container-high">
                <option value="">Todos los estados</option>
                <option value="nueva" {{ request('estado') == 'nueva' ? 'selected' : '' }}>Nueva</option>
                <option value="en_revision" {{ request('estado') == 'en_revision' ? 'selected' : '' }}>En revisión</option>
                <option value="priorizada" {{ request('estado') == 'priorizada' ? 'selected' : '' }}>Priorizada</option>
                <option value="en_desarrollo" {{ request('estado') == 'en_desarrollo' ? 'selected' : '' }}>En desarrollo</option>
                <option value="implementada" {{ request('estado') == 'implementada' ? 'selected' : '' }}>Implementada</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('ranking.index') }}" class="px-3 py-2 text-xs font-medium text-outline hover:text-primary">Limpiar</a>
        </div>
    </form>

    <!-- Top 3 Podium Cards -->
    @if($top3->isNotEmpty())
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end pt-4">
        
        <!-- 2nd Place (Silver) -->
        @if($top3->count() >= 2)
        @php $idea2 = $top3->get(1); @endphp
        <div class="order-2 md:order-1 bg-surface-container-lowest rounded-3xl p-6 border border-slate-200 shadow-sm relative overflow-hidden flex flex-col justify-between group hover:shadow-md transition-shadow">
            <div class="absolute top-0 right-0 p-4">
                <span class="text-3xl">🥈</span>
            </div>
            <div>
                <span class="font-mono-tech text-[11px] font-bold uppercase tracking-wider text-slate-500 block mb-2">Puesto #2</span>
                <a href="{{ route('ideas.show', $idea2->slug) }}" class="block group-hover:text-primary transition-colors">
                    <h3 class="font-headline font-bold text-base text-on-surface line-clamp-2 mb-2">{{ $idea2->title }}</h3>
                </a>
                <p class="text-xs text-on-surface-variant line-clamp-2 mb-4">{{ $idea2->summary }}</p>
            </div>
            <div class="pt-4 border-t border-surface-container-high/60 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <img src="{{ $idea2->user->avatar_url }}" alt="{{ $idea2->user->name }}" class="w-7 h-7 rounded-full object-cover">
                    <span class="text-xs font-semibold text-on-surface truncate">{{ $idea2->user->name }}</span>
                </div>
                <div class="text-right">
                    <span class="font-headline font-extrabold text-lg text-primary block leading-none">{{ $idea2->innovation_score }}</span>
                    <span class="text-[9px] font-mono-tech text-outline uppercase">Score</span>
                </div>
            </div>
        </div>
        @endif

        <!-- 1st Place (Gold) -->
        @if($top3->count() >= 1)
        @php $idea1 = $top3->get(0); @endphp
        <div class="order-1 md:order-2 bg-gradient-to-b from-amber-50 to-surface-container-lowest rounded-3xl p-7 border-2 border-amber-300 shadow-lg relative overflow-hidden flex flex-col justify-between group md:-translate-y-4">
            <div class="absolute top-0 right-0 p-4">
                <span class="text-4xl animate-bounce">🥇</span>
            </div>
            <div>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-secondary-container text-on-secondary-container font-mono-tech text-[11px] font-bold uppercase tracking-wider mb-3">
                    Líder del Ranking
                </span>
                <a href="{{ route('ideas.show', $idea1->slug) }}" class="block group-hover:text-primary transition-colors">
                    <h3 class="font-headline font-extrabold text-lg sm:text-xl text-on-surface line-clamp-2 mb-2">{{ $idea1->title }}</h3>
                </a>
                <p class="text-xs sm:text-sm text-on-surface-variant line-clamp-3 mb-4">{{ $idea1->summary }}</p>
            </div>
            <div class="pt-4 border-t border-amber-200/60 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <img src="{{ $idea1->user->avatar_url }}" alt="{{ $idea1->user->name }}" class="w-9 h-9 rounded-full object-cover ring-2 ring-amber-300">
                    <div>
                        <span class="text-xs font-bold text-on-surface block">{{ $idea1->user->name }}</span>
                        <span class="text-[10px] text-on-surface-variant block">{{ $idea1->user->department }}</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="font-headline font-extrabold text-2xl text-secondary block leading-none">{{ $idea1->innovation_score }}</span>
                    <span class="text-[10px] font-mono-tech text-secondary uppercase font-bold">Innovation Score</span>
                </div>
            </div>
        </div>
        @endif

        <!-- 3rd Place (Bronze) -->
        @if($top3->count() >= 3)
        @php $idea3 = $top3->get(2); @endphp
        <div class="order-3 bg-surface-container-lowest rounded-3xl p-6 border border-amber-900/10 shadow-sm relative overflow-hidden flex flex-col justify-between group hover:shadow-md transition-shadow">
            <div class="absolute top-0 right-0 p-4">
                <span class="text-3xl">🥉</span>
            </div>
            <div>
                <span class="font-mono-tech text-[11px] font-bold uppercase tracking-wider text-amber-700 block mb-2">Puesto #3</span>
                <a href="{{ route('ideas.show', $idea3->slug) }}" class="block group-hover:text-primary transition-colors">
                    <h3 class="font-headline font-bold text-base text-on-surface line-clamp-2 mb-2">{{ $idea3->title }}</h3>
                </a>
                <p class="text-xs text-on-surface-variant line-clamp-2 mb-4">{{ $idea3->summary }}</p>
            </div>
            <div class="pt-4 border-t border-surface-container-high/60 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <img src="{{ $idea3->user->avatar_url }}" alt="{{ $idea3->user->name }}" class="w-7 h-7 rounded-full object-cover">
                    <span class="text-xs font-semibold text-on-surface truncate">{{ $idea3->user->name }}</span>
                </div>
                <div class="text-right">
                    <span class="font-headline font-extrabold text-lg text-primary block leading-none">{{ $idea3->innovation_score }}</span>
                    <span class="text-[9px] font-mono-tech text-outline uppercase">Score</span>
                </div>
            </div>
        </div>
        @endif

    </div>
    @endif

    <!-- Leaderboard Table -->
    <div class="bg-surface-container-lowest rounded-3xl border border-surface-container-high/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-surface-container-high/60 flex items-center justify-between">
            <h2 class="font-headline font-bold text-lg text-on-surface">Tabla General de Posiciones</h2>
            <div class="flex items-center gap-1.5 text-xs text-on-surface-variant font-mono-tech" title="El Innovation Score considera valoraciones, volumen de votos, interacción y vigencia">
                <span class="material-symbols-outlined text-sm text-primary">info</span>
                <span>Fórmula ponderada</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-surface-container-low font-mono-tech text-[11px] uppercase tracking-wider text-outline border-b border-surface-container-high">
                    <tr>
                        <th class="py-3.5 px-4 font-bold text-center w-16">Pos</th>
                        <th class="py-3.5 px-4 font-bold">Idea</th>
                        <th class="py-3.5 px-4 font-bold">Autor</th>
                        <th class="py-3.5 px-4 font-bold hidden sm:table-cell">Categoría</th>
                        <th class="py-3.5 px-4 font-bold text-center">Valoración</th>
                        <th class="py-3.5 px-4 font-bold text-center">Votos</th>
                        <th class="py-3.5 px-4 font-bold text-center">Score</th>
                        <th class="py-3.5 px-4 font-bold text-right">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-container-high/60">
                    @forelse($remaining as $index => $idea)
                    <tr class="hover:bg-surface-container-low/60 transition-colors">
                        <td class="py-4 px-4 font-mono-tech font-bold text-center text-outline">
                            #{{ $index + 4 }}
                        </td>
                        <td class="py-4 px-4 font-semibold text-on-surface">
                            <a href="{{ route('ideas.show', $idea->slug) }}" class="hover:text-primary transition-colors block line-clamp-1">
                                {{ $idea->title }}
                            </a>
                        </td>
                        <td class="py-4 px-4">
                            <a href="{{ route('profile.show', $idea->user_id) }}" class="flex items-center gap-2 group">
                                <img src="{{ $idea->user->avatar_url }}" alt="{{ $idea->user->name }}" class="w-6 h-6 rounded-full object-cover">
                                <span class="group-hover:text-primary transition-colors truncate max-w-[120px]">{{ $idea->user->name }}</span>
                            </a>
                        </td>
                        <td class="py-4 px-4 text-xs text-on-surface-variant hidden sm:table-cell">
                            {{ $idea->category?->name ?: 'General' }}
                        </td>
                        <td class="py-4 px-4 text-center font-mono-tech font-bold text-on-surface">
                            ★ {{ number_format($idea->average_rating, 1) }}
                        </td>
                        <td class="py-4 px-4 text-center font-mono-tech text-outline">
                            {{ $idea->votes_count }}
                        </td>
                        <td class="py-4 px-4 text-center font-mono-tech font-bold text-primary text-sm">
                            {{ $idea->innovation_score }}
                        </td>
                        <td class="py-4 px-4 text-right">
                            <x-status-badge :status="$idea->status" />
                        </td>
                    </tr>
                    @empty
                    @if($top3->isEmpty())
                    <tr>
                        <td colspan="8" class="text-center py-12 text-on-surface-variant text-xs">
                            No hay suficientes ideas registradas para generar el ranking en este periodo.
                        </td>
                    </tr>
                    @endif
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
