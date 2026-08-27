@props(['node', 'treeByParent', 'searchTerms', 'level' => 0, 'currentIdeaId' => null])

@php
    $children = $treeByParent->get($node->id, collect());
    $hasChildren = $children->isNotEmpty();
    $branchTerms = $searchTerms->get($node->id, '');
    $isCurrent = (int) $currentIdeaId === (int) $node->id;
    $terminalCounts = collect(\App\Models\Idea::TERMINAL_WORKSPACE_STATUSES)
        ->mapWithKeys(fn (string $status) => [$status => $children->where('workspace_status', $status)->count()]);
    $terminalChildrenCount = $terminalCounts->sum();
    $statusBorder = match ($node->workspace_status) {
        'completada' => 'border-emerald-500/70',
        'archivada' => 'border-amber-500/70',
        'descartada' => 'border-error/70',
        default => ($isCurrent ? 'border-primary/45 ring-2 ring-primary/10' : 'border-surface-container-high/80'),
    };
@endphp

<div class="{{ $level > 0 ? 'ml-4 sm:ml-8 border-l-2 border-primary/15 pl-3 sm:pl-5' : '' }}"
     x-data="ideaTreeNode(@js((string) $node->id))"
     x-show="branchMatches(@js($branchTerms))">
    <div class="bg-surface-container-lowest rounded-2xl border {{ $statusBorder }} shadow-2xs p-4 sm:p-5">
        <div class="flex items-start gap-3">
            <button type="button"
                    @if($hasChildren) @click="expanded = !expanded" @endif
                    class="mt-0.5 w-8 h-8 rounded-xl flex items-center justify-center shrink-0 {{ $hasChildren ? 'bg-primary-fixed text-primary hover:bg-primary hover:text-white' : 'bg-surface-container text-outline cursor-default' }}"
                    :aria-expanded="{{ $hasChildren ? 'expanded.toString()' : 'false' }}"
                    aria-label="{{ $hasChildren ? 'Mostrar u ocultar ideas dependientes' : 'Idea sin dependencias' }}">
                <span class="material-symbols-outlined text-lg" x-text="{{ $hasChildren ? "expanded ? 'expand_more' : 'chevron_right'" : "'lightbulb'" }}"></span>
            </button>

            <div class="min-w-0 flex-1">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            @if($level === 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-primary/10 text-primary text-[10px] font-mono-tech font-bold uppercase">
                                    <span class="material-symbols-outlined text-xs">account_tree</span>
                                    Idea madre
                                </span>
                            @else
                                <span class="text-[10px] font-mono-tech font-bold uppercase text-outline">Nivel {{ $level + 1 }}</span>
                            @endif

                            @if($isCurrent)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-secondary-fixed text-on-secondary-fixed text-[10px] font-bold">Idea abierta</span>
                            @endif

                            <span class="text-[10px] font-mono-tech text-on-surface-variant">{{ $node->category?->name ?: 'Sin área' }}</span>

                            @if($node->isPublished())
                                @if($node->community_display === 'represented_by_parent')
                                    <span class="px-2 py-0.5 rounded-lg bg-tertiary/10 text-tertiary text-[10px] font-bold">Representada por la madre</span>
                                @else
                                    <x-status-badge :status="$node->status" />
                                @endif
                            @else
                                <span class="px-2 py-0.5 rounded-lg bg-surface-container text-on-surface-variant text-[10px] font-bold">{{ $node->workspace_status_label }}</span>
                            @endif
                        </div>

                        <a href="{{ route('ideas.show', $node->slug) }}" class="block font-headline font-bold text-sm sm:text-base text-on-surface hover:text-primary line-clamp-2">
                            {{ $node->title }}
                        </a>
                        <p class="mt-1 text-xs text-on-surface-variant line-clamp-2">{{ $node->summary }}</p>
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        @if($terminalChildrenCount > 0)
                            <button type="button" @click="filterOpen = true" class="inline-flex items-center gap-1 rounded-lg border border-surface-container-high bg-surface-container-lowest px-2 py-1.5 text-[10px] font-bold text-on-surface-variant hover:border-primary/35 hover:text-primary" title="Elegir estados visibles entre las subideas">
                                <span class="material-symbols-outlined text-base">filter_alt</span>
                                <span class="hidden xl:inline">Estados</span>
                            </button>
                        @endif
                        @if($hasChildren)
                            <span class="px-2 py-1 rounded-lg bg-surface-container text-[10px] font-mono-tech text-on-surface-variant">
                                {{ $children->count() }} {{ $children->count() === 1 ? 'subidea' : 'subideas' }}
                            </span>
                        @endif
                        @if(auth()->id() === $node->user_id && $node->isEditableBy(auth()->user()))
                            <a href="{{ route('ideas.create', ['parent' => $node->id]) }}" class="inline-flex items-center gap-1 rounded-lg bg-secondary-fixed/65 px-2 py-1.5 text-[10px] font-bold text-on-secondary-fixed hover:bg-secondary-container" title="Agregar una subidea">
                                <span class="material-symbols-outlined text-base">add</span>
                                <span class="hidden sm:inline">Hija</span>
                            </a>
                        @endif
                        @can('update', $node)
                            <a href="{{ route('ideas.edit', $node) }}" class="p-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface" title="Editar idea">
                                <span class="material-symbols-outlined text-base">edit</span>
                            </a>
                        @endcan
                        <a href="{{ route('ideas.show', $node->slug) }}" class="p-1.5 rounded-lg bg-primary-fixed text-primary hover:bg-primary hover:text-white" title="Abrir ficha">
                            <span class="material-symbols-outlined text-base">arrow_forward</span>
                        </a>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] font-mono-tech text-outline">
                    <span class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">{{ $node->isPublished() ? 'public' : ($node->access_scope === 'organization' ? 'corporate_fare' : ($node->access_scope === 'profile' ? 'person' : 'lock')) }}</span>
                        {{ $node->isPublished() ? 'Comunidad' : $node->access_scope_label }}
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">publish</span>
                        {{ $node->publication_status_label }}
                    </span>
                    <span>Actualizada {{ $node->updated_at->diffForHumans() }}</span>
                </div>
            </div>
        </div>
    </div>

    @if($terminalChildrenCount > 0)
        <div x-show="filterOpen" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Estados visibles en las subideas">
            <div class="fixed inset-0 bg-on-surface/45 backdrop-blur-xs" @click="filterOpen = false"></div>
            <div class="relative z-10 w-full max-w-md rounded-3xl border border-surface-container-high bg-surface-container-lowest p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4"><div><h3 class="font-headline text-lg font-bold">Subideas visibles</h3><p class="mt-1 text-xs text-on-surface-variant">La preferencia sólo afecta esta rama y se recuerda en este navegador.</p></div><button type="button" @click="filterOpen = false" class="text-outline"><span class="material-symbols-outlined">close</span></button></div>
                <div class="mt-5 space-y-3">
                    @foreach(['completada' => ['Completadas', 'border-emerald-500', $terminalCounts['completada']], 'archivada' => ['Archivadas', 'border-amber-500', $terminalCounts['archivada']], 'descartada' => ['Descartadas', 'border-error', $terminalCounts['descartada']]] as $status => [$label, $border, $count])
                        <label class="flex items-center justify-between gap-3 rounded-2xl border-l-4 {{ $border }} bg-surface-container-low p-3 text-xs"><span><strong>{{ $label }}</strong><span class="ml-1 text-outline">({{ $count }})</span></span><input type="checkbox" x-model="visibility.{{ $status }}" @change="saveVisibility()" class="rounded border-outline text-primary"></label>
                    @endforeach
                </div>
                <button type="button" @click="filterOpen = false" class="mt-5 w-full rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white">Aplicar</button>
            </div>
        </div>
    @endif

    @if($hasChildren)
        <div x-show="expanded || normalizedQuery() !== ''" class="mt-3 space-y-3">
            @foreach($children as $child)
                <div x-show="childVisible(@js($child->workspace_status))">
                    <x-idea-tree-node :node="$child" :tree-by-parent="$treeByParent" :search-terms="$searchTerms" :level="$level + 1" :current-idea-id="$currentIdeaId" />
                </div>
            @endforeach
        </div>
    @endif
</div>
