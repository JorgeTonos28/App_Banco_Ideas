@props(['context' => 'form'])

@php
    $isAdminContext = $context === 'admin';
@endphp

<aside aria-label="Guía de clasificación de ideas"
       class="overflow-hidden rounded-2xl border border-primary/20 bg-primary-fixed/35">
    <details class="group" @if($isAdminContext) open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-3 p-4 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-inset sm:p-5 [&::-webkit-details-marker]:hidden">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-white">
                <span class="material-symbols-outlined text-xl">rule</span>
            </span>

            <span class="min-w-0 flex-1">
                <span class="block font-headline text-sm font-bold text-on-surface sm:text-base">
                    {{ $isAdminContext ? 'Criterios para mantener una taxonomía útil' : 'Clasifica una vez, conecta muchas ideas' }}
                </span>
                <span class="mt-0.5 block text-xs leading-relaxed text-on-surface-variant">
                    @if($isAdminContext)
                        Decide qué conceptos merecen una dimensión, una categoría controlada o una etiqueta reutilizable.
                    @else
                        Usa la categoría para el tema, las dimensiones para el contexto y las etiquetas para los conceptos que conectan.
                    @endif
                </span>
            </span>

            <span class="hidden shrink-0 items-center gap-1.5 text-xs font-bold text-primary sm:inline-flex">
                <span class="group-open:hidden">{{ $isAdminContext ? 'Ver criterios' : 'Ver guía breve' }}</span>
                <span class="hidden group-open:inline">Ocultar guía</span>
                <span class="material-symbols-outlined text-lg transition-transform group-open:rotate-180">expand_more</span>
            </span>
        </summary>

        <div class="border-t border-primary/15 bg-surface-container-lowest/75 p-4 sm:p-5">
            @if($isAdminContext)
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-8">
                    <section aria-labelledby="controlled-taxonomy-rules" class="space-y-4">
                        <div>
                            <h3 id="controlled-taxonomy-rules" class="font-headline text-sm font-bold text-on-surface">Dimensiones y categorías controladas</h3>
                            <p class="mt-1 text-xs leading-relaxed text-on-surface-variant">Una categoría organiza. Una etiqueta conecta ideas a través de diferentes categorías.</p>
                        </div>

                        <dl class="space-y-3">
                            <div class="flex gap-3">
                                <span class="material-symbols-outlined mt-0.5 text-lg text-primary">view_week</span>
                                <div>
                                    <dt class="text-xs font-bold text-on-surface">Crea una dimensión para una pregunta estable</dt>
                                    <dd class="mt-0.5 text-[11px] leading-relaxed text-on-surface-variant">Debe aplicar a la mayoría de las ideas y aportar un filtro útil, por ejemplo tipo de iniciativa o alcance.</dd>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <span class="material-symbols-outlined mt-0.5 text-lg text-primary">category</span>
                                <div>
                                    <dt class="text-xs font-bold text-on-surface">Crea un término cuando sea distinto y reutilizable</dt>
                                    <dd class="mt-0.5 text-[11px] leading-relaxed text-on-surface-variant">Como referencia, debería servir para cinco ideas actuales o previstas. Una excepción necesita valor claro de navegación.</dd>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <span class="material-symbols-outlined mt-0.5 text-lg text-primary">filter_alt_off</span>
                                <div>
                                    <dt class="text-xs font-bold text-on-surface">No dupliques campos del sistema</dt>
                                    <dd class="mt-0.5 text-[11px] leading-relaxed text-on-surface-variant">Estados, visibilidad, publicación y jerarquía ya tienen controles propios y no deben convertirse en categorías.</dd>
                                </div>
                            </div>
                        </dl>
                    </section>

                    <section aria-labelledby="tag-governance-rules" class="space-y-4">
                        <div>
                            <h3 id="tag-governance-rules" class="font-headline text-sm font-bold text-on-surface">Vocabulario de etiquetas</h3>
                            <p class="mt-1 text-xs leading-relaxed text-on-surface-variant">Mantén un catálogo breve, predecible y fácil de reutilizar.</p>
                        </div>

                        <dl class="space-y-3">
                            <div class="flex gap-3">
                                <span class="material-symbols-outlined mt-0.5 text-lg text-primary">tag</span>
                                <div>
                                    <dt class="text-xs font-bold text-on-surface">Usa entre 4 y 7 etiquetas por idea</dt>
                                    <dd class="mt-0.5 text-[11px] leading-relaxed text-on-surface-variant">Combina ecosistema, capacidades, tecnología o método y, cuando aporte, audiencia o resultado.</dd>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <span class="material-symbols-outlined mt-0.5 text-lg text-primary">spellcheck</span>
                                <div>
                                    <dt class="text-xs font-bold text-on-surface">Prefiere sustantivos de 2 a 4 palabras</dt>
                                    <dd class="mt-0.5 text-[11px] leading-relaxed text-on-surface-variant">Usa una forma canónica y conserva las siglas en mayúsculas: Inteligencia Artificial, Soporte Técnico, LLM.</dd>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <span class="material-symbols-outlined mt-0.5 text-lg text-primary">difference</span>
                                <div>
                                    <dt class="text-xs font-bold text-on-surface">Busca y fusiona antes de crear</dt>
                                    <dd class="mt-0.5 text-[11px] leading-relaxed text-on-surface-variant">Evita sinónimos, singular y plural duplicados, acciones como Crear y términos genéricos como Nueva idea.</dd>
                                </div>
                            </div>
                        </dl>
                    </section>
                </div>
            @else
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1.1fr_0.9fr] lg:gap-8">
                    <section aria-labelledby="classification-path" class="space-y-3">
                        <h3 id="classification-path" class="font-headline text-sm font-bold text-on-surface">Piensa de lo general a lo específico</h3>

                        <dl class="space-y-3">
                            <div class="flex gap-3">
                                <span class="flex h-7 min-w-7 items-center justify-center rounded-lg bg-primary text-[11px] font-bold text-white">1</span>
                                <div>
                                    <dt class="text-xs font-bold text-on-surface">Categoría: el tema principal</dt>
                                    <dd class="mt-0.5 text-[11px] leading-relaxed text-on-surface-variant">Responde dónde aporta la idea. Elige una sola categoría según el problema central.</dd>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <span class="flex h-7 min-w-7 items-center justify-center rounded-lg bg-primary text-[11px] font-bold text-white">2</span>
                                <div>
                                    <dt class="text-xs font-bold text-on-surface">Dimensiones: el contexto</dt>
                                    <dd class="mt-0.5 text-[11px] leading-relaxed text-on-surface-variant">Indican qué tipo de iniciativa es y dónde aplica. Completa cada dimensión obligatoria.</dd>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <span class="flex h-7 min-w-7 items-center justify-center rounded-lg bg-primary text-[11px] font-bold text-white">3</span>
                                <div>
                                    <dt class="text-xs font-bold text-on-surface">Etiquetas: los conceptos que conectan</dt>
                                    <dd class="mt-0.5 text-[11px] leading-relaxed text-on-surface-variant">Usa entre 4 y 7 términos concretos que ayuden a encontrar ideas relacionadas.</dd>
                                </div>
                            </div>
                        </dl>
                    </section>

                    <section aria-labelledby="tag-checklist" class="rounded-xl bg-surface-container-low p-4">
                        <h3 id="tag-checklist" class="font-headline text-sm font-bold text-on-surface">Antes de crear una etiqueta</h3>
                        <ul class="mt-3 space-y-2.5 text-[11px] leading-relaxed text-on-surface-variant">
                            <li class="flex gap-2">
                                <span class="material-symbols-outlined text-base text-primary">search</span>
                                <span>Busca una similar y reutilízala si representa el mismo concepto.</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="material-symbols-outlined text-base text-primary">check_circle</span>
                                <span>Prefiere sustantivos breves: Inteligencia Artificial, Soporte Técnico.</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="material-symbols-outlined text-base text-primary">block</span>
                                <span>Evita acciones o estados: Crear, Nueva idea, En desarrollo.</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="material-symbols-outlined text-base text-primary">account_tree</span>
                                <span>No repitas categoría, visibilidad o jerarquía como etiquetas.</span>
                            </li>
                        </ul>
                    </section>
                </div>
            @endif
        </div>
    </details>
</aside>
