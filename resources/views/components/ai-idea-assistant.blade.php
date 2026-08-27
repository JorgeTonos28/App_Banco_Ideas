@props(['currentIdeaId' => null])

<section
    x-data="ideaAiAssistant({
        transcribeUrl: @js(route('api.ai.ideas.transcribe')),
        organizeUrl: @js(route('api.ai.ideas.organize')),
        relationsUrl: @js(route('api.ai.ideas.relations')),
        currentIdeaId: @js($currentIdeaId)
    })"
    @semantic-relations-changed.window="syncConfirmedRelations($event.detail.relations)"
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
                    <p class="mt-1 max-w-2xl text-xs leading-relaxed text-on-surface-variant">Graba o escribe la idea. Podrás aplicar cada sugerencia o conservar lo que ya habías escrito; nada se guarda hasta enviar el formulario.</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button x-show="state !== 'recording'" type="button" @click="startRecording" :disabled="isBusy" class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white disabled:cursor-not-allowed disabled:opacity-50">
                    <span class="material-symbols-outlined text-base">mic</span> Grabar idea
                </button>
                <button x-show="state === 'recording'" type="button" @click="stopRecording" class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-error px-4 py-2.5 text-xs font-bold text-white">
                    <span class="material-symbols-outlined animate-pulse text-base">stop_circle</span> Detener <span x-text="formatTime()"></span>
                </button>
                <button type="button" @click="analyze" :disabled="isBusy" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-primary/30 bg-surface-container-lowest px-4 py-2.5 text-xs font-bold text-primary disabled:cursor-not-allowed disabled:opacity-50">
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
                    <div><h3 class="font-headline text-sm font-bold">Sugerencias para revisión</h3><p class="text-[11px] text-on-surface-variant">Decide en cada sección si aplicas la sugerencia o conservas el contenido original.</p></div>
                    <button type="button" @click="applyAll"
                            class="cursor-pointer rounded-xl px-4 py-2 text-xs font-bold transition-colors"
                            :class="allContentApplied ? 'bg-emerald-100 text-emerald-800' : (allContentDecided ? 'bg-primary-fixed text-primary' : 'bg-primary text-white')"
                            x-text="allContentApplied ? 'Contenido y organización aplicados' : (allContentDecided ? 'Revisión completada' : 'Aplicar contenido y organización')"></button>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <article class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <h4 class="pt-1 text-xs font-bold uppercase text-outline">Título</h4>
                            <div class="flex flex-wrap justify-end gap-1">
                                <button type="button" @click="keepOriginal('title')" :aria-pressed="isOriginal('title')" class="cursor-pointer rounded-lg px-2 py-1 text-[11px] font-bold transition-colors" :class="isOriginal('title') ? 'bg-primary-fixed text-primary' : 'text-on-surface-variant hover:bg-surface-container'" x-text="isOriginal('title') ? 'Original conservado' : 'Mantener original'"></button>
                                <button type="button" @click="applyText('title')" :aria-pressed="isApplied('title')" class="cursor-pointer rounded-lg px-2 py-1 text-xs font-bold transition-colors" :class="isApplied('title') ? 'bg-emerald-100 text-emerald-800' : 'text-primary hover:bg-primary-fixed/50'" x-text="isApplied('title') ? 'Aplicado' : 'Aplicar'"></button>
                            </div>
                        </div>
                        <p class="mt-2 text-sm font-semibold" x-text="suggestion?.title"></p>
                    </article>
                    <article class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <h4 class="pt-1 text-xs font-bold uppercase text-outline">Problema u oportunidad</h4>
                            <div class="flex flex-wrap justify-end gap-1">
                                <button type="button" @click="keepOriginal('problem_opportunity')" :aria-pressed="isOriginal('problem_opportunity')" class="cursor-pointer rounded-lg px-2 py-1 text-[11px] font-bold transition-colors" :class="isOriginal('problem_opportunity') ? 'bg-primary-fixed text-primary' : 'text-on-surface-variant hover:bg-surface-container'" x-text="isOriginal('problem_opportunity') ? 'Original conservado' : 'Mantener original'"></button>
                                <button type="button" @click="applyText('problem_opportunity')" :aria-pressed="isApplied('problem_opportunity')" class="cursor-pointer rounded-lg px-2 py-1 text-xs font-bold transition-colors" :class="isApplied('problem_opportunity') ? 'bg-emerald-100 text-emerald-800' : 'text-primary hover:bg-primary-fixed/50'" x-text="isApplied('problem_opportunity') ? 'Aplicado' : 'Aplicar'"></button>
                            </div>
                        </div>
                        <p class="mt-2 text-xs leading-relaxed" x-text="suggestion?.problem_opportunity || 'La IA indica que falta información.'"></p>
                    </article>
                    <article class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4 md:col-span-2">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <h4 class="pt-1 text-xs font-bold uppercase text-outline">Descripción</h4>
                            <div class="flex flex-wrap justify-end gap-1">
                                <button type="button" @click="keepOriginal('description')" :aria-pressed="isOriginal('description')" class="cursor-pointer rounded-lg px-2 py-1 text-[11px] font-bold transition-colors" :class="isOriginal('description') ? 'bg-primary-fixed text-primary' : 'text-on-surface-variant hover:bg-surface-container'" x-text="isOriginal('description') ? 'Original conservado' : 'Mantener original'"></button>
                                <button type="button" @click="applyText('description')" :aria-pressed="isApplied('description')" class="cursor-pointer rounded-lg px-2 py-1 text-xs font-bold transition-colors" :class="isApplied('description') ? 'bg-emerald-100 text-emerald-800' : 'text-primary hover:bg-primary-fixed/50'" x-text="isApplied('description') ? 'Aplicado' : 'Aplicar'"></button>
                            </div>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-xs leading-relaxed" x-text="suggestion?.description"></p>
                    </article>
                </div>

                <div class="grid gap-3 lg:grid-cols-3">
                    <article class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <h4 class="pt-1 text-xs font-bold uppercase text-outline">Clasificación</h4>
                            <div class="flex flex-wrap justify-end gap-1">
                                <button type="button" @click="keepOriginal('classification')" :aria-pressed="isOriginal('classification')" class="cursor-pointer rounded-lg px-2 py-1 text-[11px] font-bold transition-colors" :class="isOriginal('classification') ? 'bg-primary-fixed text-primary' : 'text-on-surface-variant hover:bg-surface-container'" x-text="isOriginal('classification') ? 'Original conservado' : 'Mantener original'"></button>
                                <button type="button" @click="applyClassification" :aria-pressed="isApplied('classification')" class="cursor-pointer rounded-lg px-2 py-1 text-xs font-bold transition-colors" :class="isApplied('classification') ? 'bg-emerald-100 text-emerald-800' : 'text-primary hover:bg-primary-fixed/50'" x-text="isApplied('classification') ? 'Aplicado' : 'Aplicar'"></button>
                            </div>
                        </div>
                        <p class="mt-2 text-xs">Categoría principal: <strong x-text="suggestion?.primary_category_name || 'Sin confianza suficiente'"></strong></p>
                        <p class="mt-1 text-[11px] text-on-surface-variant" x-text="`${suggestion?.classifications?.length || 0} dimensiones sugeridas`"></p>
                    </article>
                    <article class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <h4 class="pt-1 text-xs font-bold uppercase text-outline">Etiquetas</h4>
                            <div class="flex flex-wrap justify-end gap-1">
                                <button type="button" @click="keepOriginal('tags')" :aria-pressed="isOriginal('tags')" class="cursor-pointer rounded-lg px-2 py-1 text-[11px] font-bold transition-colors" :class="isOriginal('tags') ? 'bg-primary-fixed text-primary' : 'text-on-surface-variant hover:bg-surface-container'" x-text="isOriginal('tags') ? 'Original conservado' : 'Mantener original'"></button>
                                <button type="button" @click="applyTags" :aria-pressed="isApplied('tags')" class="cursor-pointer rounded-lg px-2 py-1 text-xs font-bold transition-colors" :class="isApplied('tags') ? 'bg-emerald-100 text-emerald-800' : 'text-primary hover:bg-primary-fixed/50'" x-text="isApplied('tags') ? 'Aplicado' : 'Aplicar'"></button>
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5"><template x-for="tag in suggestion?.tags || []" :key="`${tag.name}:${tag.existing_tag_id}`"><span class="rounded-lg bg-primary-fixed px-2 py-1 text-[11px] font-semibold text-primary" x-text="`#${tag.name}`"></span></template></div>
                    </article>
                    <article class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <h4 class="pt-1 text-xs font-bold uppercase text-outline">Idea madre</h4>
                            <div class="flex flex-wrap justify-end gap-1">
                                <button type="button" @click="keepOriginal('parent')" :aria-pressed="isOriginal('parent')" class="cursor-pointer rounded-lg px-2 py-1 text-[11px] font-bold transition-colors" :class="isOriginal('parent') ? 'bg-primary-fixed text-primary' : 'text-on-surface-variant hover:bg-surface-container'" x-text="isOriginal('parent') ? 'Original conservado' : 'Mantener original'"></button>
                                <button type="button" @click="applyParent" :aria-pressed="isApplied('parent')" class="cursor-pointer rounded-lg px-2 py-1 text-xs font-bold transition-colors" :class="isApplied('parent') ? 'bg-emerald-100 text-emerald-800' : 'text-primary hover:bg-primary-fixed/50'" x-text="isApplied('parent') ? 'Aplicado' : 'Aplicar'"></button>
                            </div>
                        </div>
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
                    <button type="button" @click="suggestRelations" :disabled="isBusy"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border px-4 py-2 text-xs font-bold transition-all disabled:cursor-not-allowed disabled:opacity-50"
                            :class="relationAnalysisComplete ? 'border-emerald-300 bg-emerald-100 text-emerald-800' : 'border-primary/30 bg-surface-container-lowest text-primary'">
                        <span class="material-symbols-outlined text-base" :class="relationsJustReady ? 'animate-bounce' : ''" x-text="relationAnalysisComplete ? 'check_circle' : 'account_tree'"></span>
                        <span x-text="state === 'relations' ? 'Buscando…' : (relationAnalysisComplete ? 'Sugerencias listas' : 'Sugerir relaciones')"></span>
                    </button>
                </div>

                <div id="ai-relation-results" x-show="relationAnalysisComplete" x-transition
                     class="scroll-mt-5 space-y-3 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 transition-shadow"
                     :class="relationsJustReady ? 'ring-4 ring-emerald-200/70 shadow-lg' : ''">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h4 class="text-xs font-bold uppercase text-emerald-900">Relaciones sugeridas</h4>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold text-emerald-800">
                            <span class="material-symbols-outlined text-sm">check_circle</span>
                            Análisis listo
                        </span>
                    </div>
                    <p x-show="relationSuggestions.length === 0" class="rounded-xl bg-surface-container-lowest p-3 text-xs leading-relaxed text-on-surface-variant">No encontramos relaciones suficientemente claras con las demás ideas. Puedes añadir una manualmente en el apartado de relaciones semánticas.</p>
                    <div x-show="relationSuggestions.length" class="space-y-2">
                        <template x-for="relation in relationSuggestions" :key="`${relation.target_idea_id}:${relation.type}`">
                            <article class="flex flex-col gap-3 rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div><p class="text-sm font-bold" x-text="relation.target_title"></p><p class="mt-1 text-[11px] text-primary" x-text="relationLabel(relation.type)"></p><p class="mt-1 text-xs text-on-surface-variant" x-text="relation.rationale"></p></div>
                                <button type="button" @click="toggleRelation(relation)" class="shrink-0 cursor-pointer rounded-xl px-3 py-2 text-xs font-bold" :class="relationIsConfirmed(relation) ? 'bg-emerald-100 text-emerald-800' : 'border border-primary/30 text-primary'" x-text="relationIsConfirmed(relation) ? 'Incorporada' : 'Incorporar'"></button>
                            </article>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
