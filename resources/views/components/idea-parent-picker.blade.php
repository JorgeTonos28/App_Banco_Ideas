@props([
    'candidates',
    'selectedId' => null,
    'inputId' => 'parent_idea_id',
    'independentLabel' => 'Esta será una idea independiente',
])

@php
    $tree = app(\App\Services\IdeaTreeService::class)->prepare($candidates);
    $resolvedSelectedId = old('parent_idea_id', $selectedId);
    $selectedCandidate = $candidates->firstWhere('id', (int) $resolvedSelectedId);
    $branchTerms = $tree['searchTerms']->values()->all();
@endphp

<div x-data="ideaParentPicker(
        @js($branchTerms),
        @js((string) ($selectedCandidate?->id ?? '')),
        @js($selectedCandidate?->title ?? $independentLabel),
        @js($independentLabel)
    )"
    @keydown.escape.window="if (open) { open = false; query = ''; }">
    <input type="hidden" name="parent_idea_id" id="{{ $inputId }}" :value="selectedId" :data-selected-title="selectedTitle">

    <button type="button" @click="open = true; $nextTick(() => $refs.parentSearch?.focus())"
            class="flex w-full items-center justify-between gap-3 rounded-2xl border border-surface-container-high bg-surface-container-low px-4 py-3 text-left text-sm text-on-surface hover:border-primary/35 focus:outline-none focus:ring-2 focus:ring-primary/20"
            aria-haspopup="dialog">
        <span class="flex min-w-0 items-center gap-2.5">
            <span class="material-symbols-outlined shrink-0 text-xl text-primary" aria-hidden="true">account_tree</span>
            <span class="min-w-0">
                <span class="block text-[10px] font-mono-tech font-bold uppercase text-outline">Ubicación seleccionada</span>
                <span class="block truncate font-semibold" x-text="selectedTitle"></span>
            </span>
        </span>
        <span class="material-symbols-outlined shrink-0 text-xl text-outline" aria-hidden="true">open_in_new</span>
    </button>

    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="parent-picker-title">
        <div class="fixed inset-0 bg-on-surface/45 backdrop-blur-xs" @click="open = false; query = ''"></div>

        <div x-show="open" x-transition class="relative z-10 flex max-h-[88dvh] w-full max-w-3xl flex-col overflow-hidden rounded-3xl border border-surface-container-high bg-surface-container-lowest shadow-2xl">
            <div class="border-b border-surface-container-high p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="parent-picker-title" class="font-headline text-lg font-bold text-on-surface">Elegir idea madre</h2>
                        <p class="mt-1 text-xs leading-relaxed text-on-surface-variant">El árbol contiene únicamente tus ideas. Abre cada nivel o busca por título, descripción, problema, categoría o etiqueta.</p>
                    </div>
                    <button type="button" @click="open = false; query = ''" class="rounded-xl p-1.5 text-outline hover:bg-surface-container hover:text-on-surface" aria-label="Cerrar selector">
                        <span class="material-symbols-outlined" aria-hidden="true">close</span>
                    </button>
                </div>

                <div class="relative mt-4">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-outline" aria-hidden="true">search</span>
                    <input x-ref="parentSearch" type="search" x-model.debounce.60ms="query"
                           placeholder="Buscar sin importar espacios..."
                           class="w-full rounded-xl border border-surface-container-high bg-surface-container-low py-3 pl-10 pr-10 text-sm text-on-surface placeholder:text-outline focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <button x-show="query.length" type="button" @click="query = ''; $refs.parentSearch.focus()" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1 text-outline hover:text-on-surface" aria-label="Limpiar búsqueda">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">close</span>
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6">
                <button type="button" @click="choose('', @js($independentLabel))"
                        class="mb-3 flex w-full items-center justify-between gap-3 rounded-2xl border p-3 text-left {{ $selectedCandidate ? 'border-surface-container-high bg-surface-container-low' : 'border-primary/30 bg-primary-fixed/40' }} hover:border-primary/40">
                    <span class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-surface-container text-outline">
                            <span class="material-symbols-outlined text-lg" aria-hidden="true">home</span>
                        </span>
                        <span>
                            <span class="block text-xs font-bold text-on-surface">Sin idea madre</span>
                            <span class="mt-0.5 block text-[10px] text-on-surface-variant">La idea quedará en la capa superior.</span>
                        </span>
                    </span>
                    <span x-show="selectedId === ''" class="material-symbols-outlined text-primary" aria-hidden="true">check_circle</span>
                </button>

                @if($tree['roots']->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($tree['roots'] as $root)
                            <x-idea-parent-picker-node
                                :node="$root"
                                :tree-by-parent="$tree['byParent']"
                                :search-terms="$tree['searchTerms']"
                            />
                        @endforeach
                    </div>
                    <div x-show="!hasMatches()" class="rounded-2xl border border-dashed border-surface-container-high p-8 text-center">
                        <span class="material-symbols-outlined text-3xl text-outline" aria-hidden="true">search_off</span>
                        <p class="mt-2 text-xs font-bold text-on-surface">No hay coincidencias en tus ideas</p>
                        <p class="mt-1 text-[11px] text-on-surface-variant">Prueba con otra cadena de caracteres.</p>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-surface-container-high p-8 text-center">
                        <span class="material-symbols-outlined text-3xl text-outline" aria-hidden="true">account_tree</span>
                        <p class="mt-2 text-xs font-bold text-on-surface">Todavía no tienes otras ideas disponibles</p>
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-surface-container-high bg-surface-container-low px-5 py-3">
                <span class="truncate text-[11px] text-on-surface-variant">Selección: <strong class="text-on-surface" x-text="selectedTitle"></strong></span>
                <button type="button" @click="open = false; query = ''" class="shrink-0 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white hover:bg-primary-container">Confirmar</button>
            </div>
        </div>
    </div>
</div>
