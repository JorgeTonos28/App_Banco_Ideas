@props(['currentIdeaId' => null])

<section
    x-data="ideaAiAssistant({
        transcribeUrl: @js(route('api.ai.ideas.transcribe')),
        organizeUrl: @js(route('api.ai.ideas.organize')),
        relationsUrl: @js(route('api.ai.ideas.relations')),
        currentIdeaId: @js($currentIdeaId)
    })"
    class="overflow-hidden rounded-3xl border border-primary/20 bg-gradient-to-br from-primary-fixed/45 via-surface-container-lowest to-tertiary-fixed/25"
>
    <div class="p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary text-white shadow-sm">
                    <span class="material-symbols-outlined">graphic_eq</span>
                </div>
                <div>
                    <h2 class="font-headline text-base font-bold text-on-surface">Captura asistida por IA</h2>
                    <p class="mt-1 max-w-2xl text-xs leading-relaxed text-on-surface-variant">Graba o escribe la idea. Podrás revisar y aplicar cada sugerencia; nada se guarda hasta enviar el formulario.</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button x-show="state !== 'recording'" type="button" @click="startRecording" :disabled="isBusy" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white disabled:opacity-50">
                    <span class="material-symbols-outlined text-base">mic</span> Grabar idea
                </button>
                <button x-show="state === 'recording'" type="button" @click="stopRecording" class="inline-flex items-center gap-2 rounded-xl bg-error px-4 py-2.5 text-xs font-bold text-white">
                    <span class="material-symbols-outlined animate-pulse text-base">stop_circle</span> Detener <span x-text="formatTime()"></span>
                </button>
                <button type="button" @click="analyze" :disabled="isBusy" class="inline-flex items-center gap-2 rounded-xl border border-primary/30 bg-surface-container-lowest px-4 py-2.5 text-xs font-bold text-primary disabled:opacity-50">
                    <span class="material-symbols-outlined text-base">auto_awesome</span> Analizar borrador
                </button>
            </div>
        </div>

        <div x-show="isBusy" x-cloak class="mt-4 flex items-center gap-2 rounded-xl bg-surface-container-lowest/75 p-3 text-xs font-semibold text-on-surface-variant">
            <span class="material-symbols-outlined animate-spin text-base text-primary">progress_activity</span>
            <span x-text="state === 'transcribing' ? 'Transcribiendo el audio…' : (state === 'relations' ? 'Buscando relaciones útiles…' : 'Organizando la idea…')"></span>
        </div>

        <div x-show="error" x-cloak class="mt-4 rounded-xl border border-error/25 bg-error-container p-3 text-xs text-on-error-container" x-text="error"></div>

        <div class="mt-5 space-y-5">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-on-surface">Transcripción editable</label>
                <textarea x-model="transcript" rows="4" maxlength="20000" placeholder="También puedes escribir aquí una idea y pulsar Analizar borrador." class="w-full rounded-2xl border border-surface-container-high bg-surface-container-lowest p-3 text-sm text-on-surface"></textarea>
            </div>

            <div x-show="suggestion" class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h3 class="font-headline text-sm font-bold">Sugerencias para revisión</h3><p class="text-[11px] text-on-surface-variant">Aplica sólo lo que represente correctamente tu intención.</p></div>
                    <button type="button" @click="applyAll" class="rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white">Aplicar contenido y organización</button>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <article class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4">
                        <div class="flex items-center justify-between gap-2"><h4 class="text-xs font-bold uppercase text-outline">Título</h4><button type="button" @click="applyText('title')" class="text-xs font-bold text-primary">Aplicar</button></div>
                        <p class="mt-2 text-sm font-semibold" x-text="suggestion?.title"></p>
                    </article>
                    <article class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4">
                        <div class="flex items-center justify-between gap-2"><h4 class="text-xs font-bold uppercase text-outline">Problema u oportunidad</h4><button type="button" @click="applyText('problem_opportunity')" class="text-xs font-bold text-primary">Aplicar</button></div>
                        <p class="mt-2 text-xs leading-relaxed" x-text="suggestion?.problem_opportunity || 'La IA indica que falta información.'"></p>
                    </article>
                    <article class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4 md:col-span-2">
                        <div class="flex items-center justify-between gap-2"><h4 class="text-xs font-bold uppercase text-outline">Descripción</h4><button type="button" @click="applyText('description')" class="text-xs font-bold text-primary">Aplicar</button></div>
                        <p class="mt-2 whitespace-pre-line text-xs leading-relaxed" x-text="suggestion?.description"></p>
                    </article>
                </div>

                <div class="grid gap-3 lg:grid-cols-3">
                    <article class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4">
                        <div class="flex items-center justify-between"><h4 class="text-xs font-bold uppercase text-outline">Clasificación</h4><button type="button" @click="applyClassification" class="text-xs font-bold text-primary">Aplicar</button></div>
                        <p class="mt-2 text-xs">Categoría principal: <strong x-text="suggestion?.primary_category_name || 'Sin confianza suficiente'"></strong></p>
                        <p class="mt-1 text-[11px] text-on-surface-variant" x-text="`${suggestion?.classifications?.length || 0} dimensiones sugeridas`"></p>
                    </article>
                    <article class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4">
                        <div class="flex items-center justify-between"><h4 class="text-xs font-bold uppercase text-outline">Etiquetas</h4><button type="button" @click="applyTags" class="text-xs font-bold text-primary">Aplicar</button></div>
                        <div class="mt-2 flex flex-wrap gap-1.5"><template x-for="tag in suggestion?.tags || []" :key="`${tag.name}:${tag.existing_tag_id}`"><span class="rounded-lg bg-primary-fixed px-2 py-1 text-[11px] font-semibold text-primary" x-text="`#${tag.name}`"></span></template></div>
                    </article>
                    <article class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4">
                        <div class="flex items-center justify-between"><h4 class="text-xs font-bold uppercase text-outline">Idea madre</h4><button type="button" @click="applyParent" class="text-xs font-bold text-primary">Aplicar</button></div>
                        <p class="mt-2 text-xs font-semibold" x-text="suggestion?.parent_suggestion?.idea_title || 'Mantener como idea independiente'"></p>
                        <p class="mt-1 text-[11px] leading-relaxed text-on-surface-variant" x-text="suggestion?.parent_suggestion?.rationale"></p>
                    </article>
                </div>

                <div x-show="suggestion?.missing_information?.length" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900">
                    <p class="font-bold">Información que conviene confirmar</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5"><template x-for="item in suggestion?.missing_information || []"><li x-text="item"></li></template></ul>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-primary/15 bg-primary-fixed/25 p-4">
                    <p class="text-xs text-on-surface-variant">Después de aplicar o ajustar el contenido, busca relaciones semánticas con tus otras ideas.</p>
                    <button type="button" @click="suggestRelations" :disabled="isBusy" class="rounded-xl border border-primary/30 bg-surface-container-lowest px-4 py-2 text-xs font-bold text-primary">Sugerir relaciones</button>
                </div>

                <div x-show="relationSuggestions.length" class="space-y-2">
                    <h4 class="text-xs font-bold uppercase text-outline">Relaciones sugeridas</h4>
                    <template x-for="relation in relationSuggestions" :key="`${relation.target_idea_id}:${relation.type}`">
                        <article class="flex flex-col gap-3 rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div><p class="text-sm font-bold" x-text="relation.target_title"></p><p class="mt-1 text-[11px] text-primary" x-text="relationLabel(relation.type)"></p><p class="mt-1 text-xs text-on-surface-variant" x-text="relation.rationale"></p></div>
                            <button type="button" @click="toggleRelation(relation)" class="shrink-0 rounded-xl px-3 py-2 text-xs font-bold" :class="relationIsConfirmed(relation) ? 'bg-emerald-100 text-emerald-800' : 'border border-primary/30 text-primary'" x-text="relationIsConfirmed(relation) ? 'Incorporada' : 'Incorporar'"></button>
                        </article>
                    </template>
                </div>
            </div>
        </div>

        <template x-for="(relation, index) in confirmedRelations" :key="`${relation.target_idea_id}:${relation.type}`">
            <div>
                <input type="hidden" :name="`ai_relations[${index}][target_idea_id]`" :value="relation.target_idea_id">
                <input type="hidden" :name="`ai_relations[${index}][type]`" :value="relation.type">
                <input type="hidden" :name="`ai_relations[${index}][rationale]`" :value="relation.rationale">
            </div>
        </template>
    </div>
</section>
