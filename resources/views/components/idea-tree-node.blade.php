@props(['node', 'treeByParent', 'searchTerms', 'level' => 0, 'currentIdeaId' => null])

@php
    $children = $treeByParent->get($node->id, collect());
    $hasChildren = $children->isNotEmpty();
    $branchTerms = $searchTerms->get($node->id, '');
    $isCurrent = (int) $currentIdeaId === (int) $node->id;
@endphp

<div class="{{ $level > 0 ? 'ml-4 sm:ml-8 border-l-2 border-primary/15 pl-3 sm:pl-5' : '' }}"
     x-data="{ expanded: false }"
     x-show="branchMatches(@js($branchTerms))">
    <div class="bg-surface-container-lowest rounded-2xl border {{ $isCurrent ? 'border-primary/45 ring-2 ring-primary/10' : 'border-surface-container-high/80' }} shadow-2xs p-4 sm:p-5">
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

    @if($hasChildren)
        <div x-show="expanded || normalizedQuery() !== ''" class="mt-3 space-y-3">
            @foreach($children as $child)
                <x-idea-tree-node :node="$child" :tree-by-parent="$treeByParent" :search-terms="$searchTerms" :level="$level + 1" :current-idea-id="$currentIdeaId" />
            @endforeach
        </div>
    @endif
</div>
