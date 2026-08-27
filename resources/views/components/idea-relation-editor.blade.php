@props([
    'candidates',
    'relations' => null,
])

@php
    $typeLabels = collect(\App\Models\IdeaRelation::TYPES)->mapWithKeys(
        fn (string $type) => [$type => (new \App\Models\IdeaRelation(['type' => $type]))->type_label]
    );
    $candidateOptions = $candidates->map(fn (\App\Models\Idea $candidate) => [
        'id' => (string) $candidate->id,
        'title' => $candidate->title,
        'author' => $candidate->user?->name ?? 'Autor no disponible',
        'author_id' => (string) $candidate->user_id,
        'is_own' => $candidate->user_id === auth()->id(),
    ])->values();
    $ownCandidateTree = app(\App\Services\IdeaTreeService::class)->prepare($candidates->where('user_id', auth()->id())->values());
    $otherCandidateGroups = $candidates->where('user_id', '!=', auth()->id())->groupBy('user_id')->map(function ($ideas) {
        return [
            'user' => $ideas->first()->user,
            'tree' => app(\App\Services\IdeaTreeService::class)->prepare($ideas->values()),
        ];
    });
    $storedRelations = collect($relations ?? [])->map(fn (\App\Models\IdeaRelation $relation) => [
        'id' => $relation->id,
        'target_idea_id' => (string) $relation->target_idea_id,
        'target_title' => $relation->targetIdea->title,
        'target_author' => $relation->targetIdea->user?->name ?? 'Autor no disponible',
        'type' => $relation->type,
        'rationale' => $relation->rationale,
        'status' => $relation->status,
        'status_label' => $relation->status_label,
    ])->values();
    $initialRelations = old('idea_relations_present')
        ? collect(old('idea_relations', []))->map(function (array $relation) use ($storedRelations, $candidateOptions) {
            $stored = $storedRelations->firstWhere('id', (int) ($relation['id'] ?? 0));
            $candidate = $candidateOptions->firstWhere('id', (string) ($relation['target_idea_id'] ?? ''));

            return array_merge($stored ?? [], $relation, [
                'target_title' => $stored['target_title'] ?? $candidate['title'] ?? 'Idea relacionada',
                'target_author' => $stored['target_author'] ?? $candidate['author'] ?? '',
            ]);
        })->values()
        : $storedRelations;
@endphp

<section
    x-data="ideaRelationEditor({
        candidates: @js($candidateOptions),
        initialRelations: @js($initialRelations),
        types: @js($typeLabels)
    })"
    @ai-relation-toggle-request.window="handleAiToggle($event.detail)"
    class="overflow-hidden rounded-3xl border border-tertiary/20 bg-tertiary-fixed/15"
>
    <input type="hidden" name="idea_relations_present" value="1">

    <div class="border-b border-tertiary/15 p-5 sm:p-6">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-tertiary text-white">
                <span class="material-symbols-outlined" aria-hidden="true">hub</span>
            </span>
            <div>
                <h3 class="font-headline text-sm font-bold text-on-surface">Relaciones semánticas</h3>
                <p class="mt-1 text-xs leading-relaxed text-on-surface-variant">Conecta esta propuesta con otras ideas. Puedes añadir relaciones manualmente o incorporar las sugeridas por la IA; los cambios se guardarán junto con la idea.</p>
            </div>
        </div>
    </div>

    <div class="space-y-4 p-5 sm:p-6">
        <div x-show="error" x-cloak class="rounded-xl border border-error/20 bg-error-container/60 p-3 text-xs text-on-error-container" x-text="error"></div>
        @error('idea_relations')<p class="rounded-xl border border-error/20 bg-error-container/60 p-3 text-xs text-on-error-container">{{ $message }}</p>@enderror
        @if($errors->has('idea_relations.*'))
            <p class="text-xs text-error">{{ collect($errors->get('idea_relations.*'))->flatten()->first() }}</p>
        @endif

        <div x-show="relations.length === 0" class="rounded-2xl border border-dashed border-tertiary/25 bg-surface-container-lowest/70 p-5 text-center">
            <span class="material-symbols-outlined text-2xl text-tertiary/60" aria-hidden="true">conversion_path</span>
            <p class="mt-1 text-xs font-semibold text-on-surface">Todavía no hay relaciones incorporadas.</p>
            <p class="mt-1 text-[11px] text-on-surface-variant">Añade una manualmente o solicita sugerencias en el asistente de IA.</p>
        </div>

        <div x-show="relations.length" class="space-y-3">
            <template x-for="(relation, index) in relations" :key="relation.client_key">
                <article class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4">
                    <template x-if="relation.id">
                        <input type="hidden" :name="`idea_relations[${index}][id]`" :value="relation.id">
                    </template>
                    <input type="hidden" :name="`idea_relations[${index}][target_idea_id]`" :value="relation.target_idea_id">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate text-sm font-bold text-on-surface" x-text="relation.target_title"></p>
                                <span x-show="relation.status_label" class="rounded-full px-2 py-0.5 text-[9px] font-bold"
                                      :class="relation.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : (relation.status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-error-container/50 text-error')"
                                      x-text="relation.status_label"></span>
                            </div>
                            <p class="mt-0.5 text-[10px] text-outline" x-text="relation.target_author"></p>
                        </div>
                        <button type="button" @click="removeRelation(index)" class="inline-flex shrink-0 cursor-pointer items-center gap-1 rounded-lg px-2 py-1.5 text-[11px] font-bold text-error hover:bg-error-container/50">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">delete</span>
                            Quitar
                        </button>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-bold text-on-surface">Tipo de relación</label>
                            <select x-model="relation.type" @change="notifyAssistant" :name="`idea_relations[${index}][type]`" required class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs">
                                @foreach($typeLabels as $type => $label)
                                    <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-bold text-on-surface">Justificación</label>
                            <textarea x-model="relation.rationale" :name="`idea_relations[${index}][rationale]`" rows="2" maxlength="1000" placeholder="Explica por qué existe esta conexión" class="w-full resize-y rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs"></textarea>
                        </div>
                    </div>
                </article>
            </template>
        </div>

        <div class="rounded-2xl border border-tertiary/15 bg-surface-container-lowest/75 p-4">
            <div class="mb-3">
                <p class="text-[10px] font-mono-tech font-bold uppercase text-tertiary">Añadir conexión manual</p>
                <p class="mt-1 text-[11px] text-on-surface-variant">Si conectas ideas de autores distintos, la relación puede requerir confirmación.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold text-on-surface">Idea conectada</label>
                    <button type="button" @click="pickerOpen=true; $nextTick(() => $refs.relationSearch?.focus())" class="flex w-full items-center justify-between gap-3 rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-left text-xs"><span class="truncate font-semibold" x-text="selectedCandidateLabel()"></span><span class="material-symbols-outlined text-base text-tertiary">open_in_new</span></button>
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold text-on-surface">Tipo de relación</label>
                    <select x-model="draftType" class="w-full rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs">
                        @foreach($typeLabels as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <label class="mb-1.5 block text-[11px] font-bold text-on-surface">Justificación</label>
                <textarea x-model="draftRationale" rows="2" maxlength="1000" placeholder="Explica brevemente la conexión" class="w-full resize-y rounded-xl border border-surface-container-high bg-surface-container-low p-3 text-xs"></textarea>
            </div>

            <div class="mt-3 flex justify-end">
                <button type="button" @click="addManualRelation" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-tertiary px-4 py-2.5 text-xs font-bold text-white">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">add_link</span>
                    Incorporar relación
                </button>
            </div>
        </div>

        <div x-show="pickerOpen" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Elegir idea relacionada">
            <div class="fixed inset-0 bg-on-surface/45 backdrop-blur-xs" @click="pickerOpen=false; candidateQuery='' "></div>
            <div class="relative z-10 flex max-h-[88dvh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-surface-container-high bg-surface-container-lowest shadow-2xl">
                <div class="border-b border-surface-container-high p-5"><div class="flex items-start justify-between gap-4"><div><h3 class="font-headline text-lg font-bold">Elegir idea relacionada</h3><p class="mt-1 text-xs text-on-surface-variant">Explora tus árboles o las ideas accesibles de otros colaboradores. Las conexiones entre autores requieren confirmación.</p></div><button type="button" @click="pickerOpen=false; candidateQuery=''" class="text-outline"><span class="material-symbols-outlined">close</span></button></div><div class="relative mt-4"><span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-outline">search</span><input x-ref="relationSearch" x-model.debounce.60ms="candidateQuery" type="search" placeholder="Buscar por título, descripción, categoría o etiqueta..." class="w-full rounded-xl border border-surface-container-high bg-surface-container-low py-3 pl-10 pr-3 text-sm"></div><div class="mt-4 flex gap-2"><button type="button" @click="pickerTab='mine'" :class="pickerTab==='mine' ? 'bg-primary text-white' : 'bg-surface-container text-on-surface-variant'" class="rounded-xl px-4 py-2 text-xs font-bold">Mis ideas</button><button type="button" @click="pickerTab='others'" :class="pickerTab==='others' ? 'bg-primary text-white' : 'bg-surface-container text-on-surface-variant'" class="rounded-xl px-4 py-2 text-xs font-bold">Ideas de otros</button></div></div>
                <div class="min-h-0 flex-1 overflow-y-auto p-5">
                    <div x-show="pickerTab==='mine'" class="space-y-3">@forelse($ownCandidateTree['roots'] as $root)<x-idea-relation-picker-node :node="$root" :tree-by-parent="$ownCandidateTree['byParent']" :search-terms="$ownCandidateTree['searchTerms']" />@empty<p class="rounded-2xl border border-dashed border-surface-container-high p-8 text-center text-xs text-outline">No hay ideas propias disponibles.</p>@endforelse</div>
                    <div x-show="pickerTab==='others'" class="space-y-4">@forelse($otherCandidateGroups as $group)<section x-data="{ authorOpen: false }" class="rounded-2xl border border-surface-container-high bg-surface-container-low p-3"><button type="button" @click="authorOpen=!authorOpen" class="flex w-full items-center justify-between gap-3 text-left"><span><strong class="block text-xs">{{ $group['user']?->name ?: 'Autor no disponible' }}</strong><span class="text-[10px] text-outline">{{ $group['tree']['roots']->count() }} ideas madre visibles</span></span><span class="material-symbols-outlined text-primary" x-text="authorOpen ? 'expand_less' : 'expand_more'"></span></button><div x-show="authorOpen || normalizedCandidateQuery() !== ''" class="mt-3 space-y-3">@foreach($group['tree']['roots'] as $root)<x-idea-relation-picker-node :node="$root" :tree-by-parent="$group['tree']['byParent']" :search-terms="$group['tree']['searchTerms']" />@endforeach</div></section>@empty<p class="rounded-2xl border border-dashed border-surface-container-high p-8 text-center text-xs text-outline">No hay ideas accesibles de otros colaboradores.</p>@endforelse</div>
                </div>
            </div>
        </div>
    </div>
</section>
