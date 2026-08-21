@extends('layouts.app')

@section('title', 'Panel de Innovación - Administración INNOVATEP')

@section('content')
<div class="space-y-8">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-3xl">admin_panel_settings</span>
                <span>Panel de Innovación Institucional</span>
            </h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Métricas globales, seguimiento del flujo de ideas y gestión del banco</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.ideas.index') }}" 
               class="px-4 py-2.5 bg-primary text-white font-headline font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container transition-colors">
                Gestionar Ideas
            </a>
        </div>
    </div>

    <!-- Core Metrics Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
        <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high text-center">
            <span class="text-[10px] font-mono-tech uppercase font-bold text-outline block">Recibidas</span>
            <span class="font-headline font-extrabold text-xl sm:text-2xl text-on-surface mt-1 block">{{ $totalIdeas }}</span>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high text-center">
            <span class="text-[10px] font-mono-tech uppercase font-bold text-primary block">Nuevas (Mes)</span>
            <span class="font-headline font-extrabold text-xl sm:text-2xl text-primary mt-1 block">{{ $newThisMonth }}</span>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high text-center">
            <span class="text-[10px] font-mono-tech uppercase font-bold text-secondary block">En Revisión</span>
            <span class="font-headline font-extrabold text-xl sm:text-2xl text-secondary mt-1 block">{{ $inReview }}</span>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high text-center">
            <span class="text-[10px] font-mono-tech uppercase font-bold text-primary-container block">Priorizadas</span>
            <span class="font-headline font-extrabold text-xl sm:text-2xl text-primary-container mt-1 block">{{ $prioritized }}</span>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high text-center">
            <span class="text-[10px] font-mono-tech uppercase font-bold text-tertiary block">En Desarrollo</span>
            <span class="font-headline font-extrabold text-xl sm:text-2xl text-tertiary mt-1 block">{{ $inDevelopment }}</span>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high text-center">
            <span class="text-[10px] font-mono-tech uppercase font-bold text-emerald-700 block">Implementadas</span>
            <span class="font-headline font-extrabold text-xl sm:text-2xl text-emerald-700 mt-1 block">{{ $implemented }}</span>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high text-center">
            <span class="text-[10px] font-mono-tech uppercase font-bold text-error block">Descartadas</span>
            <span class="font-headline font-extrabold text-xl sm:text-2xl text-error mt-1 block">{{ $discarded }}</span>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-container-high text-center">
            <span class="text-[10px] font-mono-tech uppercase font-bold text-on-surface block">Usuarios</span>
            <span class="font-headline font-extrabold text-xl sm:text-2xl text-on-surface mt-1 block">{{ $activeUsers }}</span>
        </div>
    </div>

    <!-- Alert / Attention Widget -->
    @if($pendingIdeas->isNotEmpty())
    <div class="bg-gradient-to-r from-secondary-fixed/50 via-secondary-fixed/30 to-surface-container-lowest rounded-3xl p-6 border border-secondary-container/40 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-secondary-container text-on-secondary-container flex items-center justify-center font-headline font-bold text-lg shrink-0">
                {{ $pendingIdeas->count() }}
            </div>
            <div>
                <h3 class="font-headline font-bold text-base text-on-surface">Ideas que requieren atención inmediata</h3>
                <p class="text-xs text-on-surface-variant">Propuestas en estado 'Nueva' o 'En revisión' esperando asignación o retroalimentación.</p>
            </div>
        </div>

        <a href="{{ route('admin.ideas.index', ['estado' => 'nueva']) }}" 
           class="px-5 py-2.5 bg-secondary-container text-on-secondary-container font-headline font-bold text-xs rounded-xl shadow-xs hover:bg-secondary transition-colors">
            Revisar Ideas Pendientes
        </a>
    </div>
    @endif

    <!-- Breakdown Grids (Status & Categories) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Categories & Status Breakdown (7 cols) -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- Category Breakdown Card -->
            <div class="bg-surface-container-lowest rounded-3xl p-6 border border-surface-container-high/80 shadow-xs space-y-4">
                <h2 class="font-headline font-bold text-base text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">category</span>
                    <span>Distribución por Categorías</span>
                </h2>

                <div class="space-y-3">
                    @foreach($categoriesBreakdown as $category)
                    @php 
                    $percent = $totalIdeas > 0 ? round(($category->ideas_count / $totalIdeas) * 100) : 0; 
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs font-semibold text-on-surface mb-1">
                            <span class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm" style="color: {{ $category->color }}">{{ $category->icon }}</span>
                                <span>{{ $category->name }}</span>
                            </span>
                            <span class="font-mono-tech">{{ $category->ideas_count }} ({{ $percent }}%)</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-surface-container overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500" style="width: {{ $percent }}%; background-color: {{ $category->color }}"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="bg-surface-container-lowest rounded-3xl p-6 border border-surface-container-high/80 shadow-xs space-y-4">
                <h2 class="font-headline font-bold text-base text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">pie_chart</span>
                    <span>Flujo de Ideas por Estado</span>
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($statusCounts as $st => $count)
                    <div class="p-3 rounded-2xl bg-surface-container-low border border-surface-container-high">
                        <x-status-badge :status="$st" />
                        <span class="font-headline font-bold text-lg text-on-surface block mt-2">{{ $count }} ideas</span>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- Right: Active Departments & Top Rated (5 cols) -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Most Active Departments -->
            <div class="bg-surface-container-lowest rounded-3xl p-6 border border-surface-container-high/80 shadow-xs space-y-4">
                <h2 class="font-headline font-bold text-base text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">corporate_fare</span>
                    <span>Áreas Más Activas</span>
                </h2>

                <div class="divide-y divide-surface-container-high space-y-2.5">
                    @foreach($departmentsRanking as $dept)
                    <div class="pt-2.5 first:pt-0 flex items-center justify-between text-xs">
                        <span class="font-semibold text-on-surface">{{ $dept->department }}</span>
                        <span class="font-mono-tech font-bold text-primary px-2.5 py-1 rounded-full bg-primary-fixed">{{ $dept->ideas_count }} ideas</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Top Scored Ideas -->
            <div class="bg-surface-container-lowest rounded-3xl p-6 border border-surface-container-high/80 shadow-xs space-y-4">
                <h2 class="font-headline font-bold text-base text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary text-xl" style="font-variation-settings: 'FILL' 1;">stars</span>
                    <span>Ideas con Mayor Score</span>
                </h2>

                <div class="space-y-3">
                    @foreach($topScoredIdeas as $top)
                    <a href="{{ route('ideas.show', $top->slug) }}" class="block p-3 rounded-2xl bg-surface-container-low hover:bg-surface-container transition-colors group">
                        <p class="text-xs font-bold text-on-surface group-hover:text-primary transition-colors line-clamp-1">{{ $top->title }}</p>
                        <div class="flex items-center justify-between text-[10px] text-on-surface-variant font-mono-tech mt-1.5">
                            <span>{{ $top->user->name }}</span>
                            <span class="text-primary font-bold">Innovation Score: {{ $top->innovation_score }}</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
