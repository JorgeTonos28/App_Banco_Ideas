@props(['task', 'treeByParent', 'level' => 0, 'currentTaskId' => null])

@php
    $children = $treeByParent->get($task->id, collect());
    $isCurrent = (int) $currentTaskId === (int) $task->id;
@endphp

<div class="{{ $level > 0 ? 'ml-4 border-l-2 border-primary/15 pl-3 sm:ml-8 sm:pl-5' : '' }}" x-data="{ expanded: {{ $level < 1 ? 'true' : 'false' }} }">
    <article class="rounded-2xl border bg-surface-container-lowest p-4 shadow-2xs {{ $isCurrent ? 'border-primary/45 ring-2 ring-primary/10' : ($task->is_overdue ? 'border-error/35' : 'border-surface-container-high/80') }}">
        <div class="flex items-start gap-3">
            @can('changeStatus', $task)
            <form action="{{ route('tasks.status.update', $task) }}" method="POST" class="shrink-0">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $task->status === 'completada' ? 'pendiente' : 'completada' }}">
                <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-full border-2 {{ $task->status === 'completada' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-outline-variant text-transparent hover:border-primary hover:text-primary' }}" title="{{ $task->status === 'completada' ? 'Reabrir tarea' : 'Marcar como completada' }}">
                    <span class="material-symbols-outlined text-lg">check</span>
                </button>
            </form>
            @else
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 {{ $task->status === 'completada' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-outline-variant text-transparent' }}"><span class="material-symbols-outlined text-lg">check</span></span>
            @endcan

            <div class="min-w-0 flex-1">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 text-[10px] font-mono-tech">
                            @if($level > 0)<span class="font-bold text-outline">SUBTAREA · NIVEL {{ $level + 1 }}</span>@endif
                            <span class="rounded-lg px-2 py-0.5 font-bold {{ $task->priority === 'alta' ? 'bg-error-container/60 text-error' : 'bg-surface-container text-on-surface-variant' }}">{{ $task->priority_label }}</span>
                            @if($task->idea)<span class="text-tertiary">{{ $task->idea->title }}</span>@else<span class="text-outline">Tarea suelta</span>@endif
                        </div>
                        <a href="{{ route('tasks.show', $task) }}" class="mt-1 block font-headline text-sm font-bold text-on-surface hover:text-primary {{ $task->status === 'completada' ? 'line-through opacity-60' : '' }}">{{ $task->title }}</a>
                        @if($task->description)<p class="mt-1 line-clamp-1 text-xs text-on-surface-variant">{{ $task->description }}</p>@endif
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] text-outline">
                            @if($task->due_at)
                                <span class="inline-flex items-center gap-1 {{ $task->is_overdue ? 'font-bold text-error' : '' }}"><span class="material-symbols-outlined text-sm">event</span>{{ $task->due_at->translatedFormat('d M, h:i A') }}</span>
                            @endif
                            <span>{{ $task->assignee?->name ?: 'Sin responsable' }}</span>
                            @if($task->participation_mode === 'open')<span class="font-bold text-tertiary">Abierta a colaboración</span>@endif
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-1.5">
                        @if($children->isNotEmpty())
                            <button type="button" @click="expanded = !expanded" class="inline-flex items-center gap-1 rounded-lg bg-surface-container px-2 py-1.5 text-[10px] font-bold text-on-surface-variant">
                                <span class="material-symbols-outlined text-sm" x-text="expanded ? 'expand_less' : 'expand_more'"></span>{{ $children->count() }}
                            </button>
                        @endif
                        @can('update', $task)
                            <a href="{{ route('tasks.create', ['parent' => $task->id, 'idea' => $task->idea_id]) }}" class="inline-flex items-center gap-1 rounded-lg bg-secondary-fixed px-2 py-1.5 text-[10px] font-bold text-on-secondary-fixed" title="Agregar subtarea"><span class="material-symbols-outlined text-sm">add</span> Subtarea</a>
                        @endcan
                        <a href="{{ route('tasks.show', $task) }}" class="rounded-lg bg-primary-fixed p-1.5 text-primary" title="Abrir tarea"><span class="material-symbols-outlined text-base">arrow_forward</span></a>
                    </div>
                </div>
            </div>
        </div>
    </article>

    @if($children->isNotEmpty())
        <div x-show="expanded" class="mt-3 space-y-3">
            @foreach($children as $child)
                <x-task-tree-node :task="$child" :tree-by-parent="$treeByParent" :level="$level + 1" :current-task-id="$currentTaskId" />
            @endforeach
        </div>
    @endif
</div>
