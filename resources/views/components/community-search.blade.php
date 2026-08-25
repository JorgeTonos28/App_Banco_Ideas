@props([
    'level' => 'general',
    'placeholder' => 'Buscar en esta comunidad...',
])

<form method="GET"
      action="{{ route('community') }}"
      x-data
      x-ref="communitySearchForm"
      class="relative">
    <input type="hidden" name="nivel" value="{{ $level }}">
    <span class="material-symbols-outlined pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-outline" aria-hidden="true">search</span>
    <input type="search"
           name="q"
           value="{{ request('q') }}"
           @input.debounce.450ms="$refs.communitySearchForm.requestSubmit()"
           @if(request()->filled('q')) autofocus @endif
           autocomplete="off"
           aria-label="Buscar en esta comunidad"
           placeholder="{{ $placeholder }}"
           class="w-full rounded-xl border border-surface-container-high bg-surface-container-low py-2.5 pl-10 pr-10 text-sm text-on-surface placeholder:text-outline focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
    @if(request()->filled('q'))
        <a href="{{ route('community', ['nivel' => $level]) }}"
           aria-label="Limpiar búsqueda"
           class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1 text-outline hover:bg-surface-container-high hover:text-on-surface">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">close</span>
        </a>
    @endif
</form>
