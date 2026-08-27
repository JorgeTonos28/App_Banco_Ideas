@extends('layouts.app')

@section('title', 'Tareas - Centro de Innovación')

@section('content')
<div class="space-y-7">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="mb-1 flex items-center gap-2 text-[10px] font-mono-tech font-bold uppercase text-tertiary"><span class="material-symbols-outlined text-base">hub</span> Centro de Innovación</div>
            <h1 class="font-headline text-3xl font-extrabold text-on-surface">Tareas</h1>
            <p class="mt-1 text-sm text-on-surface-variant">Gestiona acciones sueltas y el trabajo que convierte las ideas en resultados.</p>
        </div>
        <a href="{{ route('tasks.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white shadow-md hover:bg-primary-container"><span class="material-symbols-outlined">add_task</span>Nueva tarea</a>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach([
            ['Pendientes', $metrics['pending'], 'pending_actions', 'text-primary'],
            ['Para hoy', $metrics['today'], 'today', 'text-tertiary'],
            ['Vencidas', $metrics['overdue'], 'warning', 'text-error'],
            ['Completadas', $metrics['completed'], 'task_alt', 'text-emerald-700'],
        ] as [$label, $value, $icon, $color])
            <div class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4 shadow-2xs">
                <span class="material-symbols-outlined {{ $color }}">{{ $icon }}</span>
                <div class="mt-2 font-headline text-2xl font-extrabold {{ $color }}">{{ $value }}</div>
                <div class="text-[10px] font-mono-tech font-bold uppercase text-outline">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    @php
        $tabs = ['all' => 'Mis pendientes', 'today' => 'Hoy', 'upcoming' => 'Próximas', 'no_date' => 'Sin fecha', 'community' => 'Colaborativas', 'completed' => 'Completadas'];
    @endphp
    <div class="flex gap-1 overflow-x-auto border-b border-surface-container-high">
        @foreach($tabs as $key => $label)
            <a href="{{ route('tasks.index', ['tab' => $key]) }}" class="whitespace-nowrap border-b-2 px-4 py-3 text-xs font-bold {{ $tab === $key ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if($tree['roots']->isNotEmpty())
        <div class="space-y-4">
            @foreach($tree['roots'] as $root)
                <x-task-tree-node :task="$root" :tree-by-parent="$tree['byParent']" />
            @endforeach
        </div>
    @else
        <div class="rounded-3xl border border-dashed border-surface-container-high bg-surface-container-lowest p-12 text-center">
            <span class="material-symbols-outlined text-5xl text-outline">task_alt</span>
            <h2 class="mt-3 font-headline text-lg font-bold">No hay tareas en esta vista</h2>
            <p class="mt-1 text-xs text-on-surface-variant">Crea una acción suelta o abre una idea y conviértela en trabajo concreto.</p>
        </div>
    @endif
</div>
@endsection
