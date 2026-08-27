@props(['node', 'treeByParent', 'searchTerms', 'level' => 0])

@php
    $children = $treeByParent->get($node->id, collect());
    $branchTerms = $searchTerms->get($node->id, '');
@endphp

<div class="{{ $level > 0 ? 'ml-4 border-l-2 border-primary/15 pl-3 sm:ml-7' : '' }}" x-data="{ expanded: false }" x-show="candidateMatches(@js($branchTerms))">
    <div class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-3">
        <div class="flex items-start gap-2.5">
            <button type="button" @if($children->isNotEmpty()) @click="expanded=!expanded" @endif class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $children->isNotEmpty() ? 'bg-primary-fixed text-primary' : 'bg-surface-container text-outline' }}"><span class="material-symbols-outlined text-lg" x-text="{{ $children->isNotEmpty() ? "expanded ? 'expand_more' : 'chevron_right'" : "'lightbulb'" }}"></span></button>
            <div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><div class="text-[9px] font-mono-tech font-bold uppercase text-outline">Nivel {{ $level + 1 }} · {{ $node->category?->name ?: 'Sin categoría' }}</div><p class="mt-1 line-clamp-2 text-xs font-bold text-on-surface">{{ $node->title }}</p>@if($node->summary)<p class="mt-1 line-clamp-1 text-[10px] text-on-surface-variant">{{ $node->summary }}</p>@endif</div><button type="button" @click="selectCandidate(@js((string) $node->id))" class="shrink-0 rounded-lg bg-tertiary-fixed px-3 py-1.5 text-[10px] font-bold text-tertiary hover:bg-tertiary hover:text-white">Elegir</button></div></div>
        </div>
    </div>
    @if($children->isNotEmpty())<div x-show="expanded || normalizedCandidateQuery() !== ''" class="mt-2 space-y-2">@foreach($children as $child)<x-idea-relation-picker-node :node="$child" :tree-by-parent="$treeByParent" :search-terms="$searchTerms" :level="$level + 1" />@endforeach</div>@endif
</div>
