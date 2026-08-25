@props(['node', 'treeByParent', 'searchTerms', 'level' => 0])

@php
    $children = $treeByParent->get($node->id, collect());
    $hasChildren = $children->isNotEmpty();
    $branchTerms = $searchTerms->get($node->id, '');
@endphp

<div class="{{ $level > 0 ? 'ml-4 border-l-2 border-primary/15 pl-3 sm:ml-7 sm:pl-4' : '' }}"
     x-data="{ expanded: false }"
     x-show="branchMatches(@js($branchTerms))">
    <div class="rounded-2xl border border-surface-container-high/80 bg-surface-container-lowest p-3 hover:border-primary/25">
        <div class="flex items-start gap-2.5">
            <button type="button" @if($hasChildren) @click="expanded = !expanded" @endif
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $hasChildren ? 'bg-primary-fixed text-primary hover:bg-primary hover:text-white' : 'bg-surface-container text-outline' }}"
                    :aria-expanded="{{ $hasChildren ? 'expanded.toString()' : 'false' }}"
                    aria-label="{{ $hasChildren ? 'Mostrar u ocultar subideas' : 'Idea sin subideas' }}">
                <span class="material-symbols-outlined text-lg" x-text="{{ $hasChildren ? "expanded ? 'expand_more' : 'chevron_right'" : "'lightbulb'" }}" aria-hidden="true"></span>
            </button>

            <div class="min-w-0 flex-1">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 text-[10px] text-outline">
                            <span class="font-mono-tech font-bold">Nivel {{ $level + 1 }}</span>
                            <span>{{ $node->category?->name ?: 'Sin categoría' }}</span>
                            @if($hasChildren)<span>{{ $children->count() }} {{ $children->count() === 1 ? 'subidea' : 'subideas' }}</span>@endif
                        </div>
                        <p class="mt-1 line-clamp-2 text-xs font-bold text-on-surface">{{ $node->title }}</p>
                        @if($node->summary)<p class="mt-1 line-clamp-1 text-[11px] text-on-surface-variant">{{ $node->summary }}</p>@endif
                    </div>
                    <button type="button" @click="choose(@js((string) $node->id), @js($node->title))"
                            class="inline-flex shrink-0 items-center justify-center gap-1 rounded-lg px-3 py-1.5 text-[11px] font-bold"
                            :class="selectedId === @js((string) $node->id) ? 'bg-primary text-white' : 'bg-primary-fixed text-primary hover:bg-primary hover:text-white'">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true" x-text="selectedId === @js((string) $node->id) ? 'check' : 'add'"></span>
                        <span x-text="selectedId === @js((string) $node->id) ? 'Seleccionada' : 'Elegir'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($hasChildren)
        <div x-show="expanded || normalizedQuery() !== ''" class="mt-3 space-y-3">
            @foreach($children as $child)
                <x-idea-parent-picker-node :node="$child" :tree-by-parent="$treeByParent" :search-terms="$searchTerms" :level="$level + 1" />
            @endforeach
        </div>
    @endif
</div>
