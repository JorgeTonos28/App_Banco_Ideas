@props(['unit', 'treeByParent', 'level' => 0])

@php
    $children = $treeByParent->get($unit->id, collect());
    $hasChildren = $children->isNotEmpty();
    $memberCount = max((int) ($unit->members_count ?? 0), (int) ($unit->users_count ?? 0));
@endphp

<div class="{{ $level > 0 ? 'ml-4 border-l-2 border-primary/15 pl-3 sm:ml-8 sm:pl-5' : '' }}" x-data="{ expanded: true }">
    <div class="rounded-2xl border border-surface-container-high/80 bg-surface-container-lowest p-4">
        <div class="flex items-start gap-3">
            <button type="button" @if($hasChildren) @click="expanded = !expanded" @endif
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $hasChildren ? 'bg-primary-fixed text-primary hover:bg-primary hover:text-white' : 'bg-surface-container text-outline' }}"
                    aria-label="{{ $hasChildren ? 'Mostrar u ocultar unidades dependientes' : 'Unidad sin niveles dependientes' }}">
                <span class="material-symbols-outlined text-lg" x-text="{{ $hasChildren ? "expanded ? 'expand_more' : 'chevron_right'" : "'domain'" }}" aria-hidden="true"></span>
            </button>

            <div class="min-w-0 flex-1">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-lg bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary">{{ $unit->type_label }}</span>
                            <span class="font-mono-tech text-[10px] font-bold text-outline">{{ $unit->code }}</span>
                            <span class="rounded-lg px-2 py-0.5 text-[10px] font-bold {{ $unit->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-error-container text-on-error-container' }}">
                                {{ $unit->is_active ? 'Habilitada' : 'Inhabilitada' }}
                            </span>
                        </div>
                        <h3 class="mt-1.5 font-headline text-sm font-bold text-on-surface sm:text-base">{{ $unit->name }}</h3>
                        <p class="mt-1 text-[10px] text-on-surface-variant">{{ $memberCount }} {{ $memberCount === 1 ? 'colaborador directo' : 'colaboradores directos' }}@if($hasChildren) · {{ $children->count() }} {{ $children->count() === 1 ? 'nivel dependiente' : 'niveles dependientes' }}@endif</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <button type="button"
                                @click="currentUnit = { id: '{{ $unit->id }}', type: '{{ $unit->type }}', parent_id: '{{ $unit->parent_id }}', code: '{{ addslashes($unit->code) }}', name: '{{ addslashes($unit->name) }}', order: {{ $unit->order }} }; editModal = true"
                                class="rounded-lg bg-surface-container px-2.5 py-1.5 text-[11px] font-bold text-on-surface hover:bg-surface-container-high">
                            Editar
                        </button>
                        <form action="{{ route('admin.regionals.status', $unit) }}" method="POST">
                            @csrf
                            <button type="submit" class="rounded-lg px-2.5 py-1.5 text-[11px] font-bold {{ $unit->is_active ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $unit->is_active ? 'Inhabilitar' : 'Habilitar' }}
                            </button>
                        </form>
                        @if(!$hasChildren && $memberCount === 0)
                            <form action="{{ route('admin.regionals.destroy', $unit) }}" method="POST" onsubmit="return confirm('¿Eliminar esta unidad organizacional?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg p-1.5 text-error hover:bg-error-container/40" aria-label="Eliminar unidad">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">delete</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($hasChildren)
        <div x-show="expanded" class="mt-3 space-y-3">
            @foreach($children as $child)
                <x-organizational-unit-node :unit="$child" :tree-by-parent="$treeByParent" :level="$level + 1" />
            @endforeach
        </div>
    @endif
</div>
