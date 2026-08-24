@props(['dimensions', 'selected' => []])

@php
    $secondaryDimensions = $dimensions->where('is_primary', false);
@endphp

@if($secondaryDimensions->isNotEmpty())
<section class="rounded-2xl border border-surface-container-high bg-surface-container-low/55 p-4 sm:p-5 space-y-5">
    <div class="flex items-start gap-3">
        <div class="w-9 h-9 rounded-xl bg-tertiary/10 text-tertiary flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-xl">category</span>
        </div>
        <div>
            <h3 class="font-headline font-bold text-sm text-on-surface">Clasificación multidimensional</h3>
            <p class="text-xs text-on-surface-variant mt-0.5">Estas dimensiones permiten encontrar la idea por alcance, tipo, beneficiarios y otros criterios.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        @foreach($secondaryDimensions as $dimension)
        @php
            $oldSelection = collect(old("classifications.{$dimension->id}", $selected[$dimension->id] ?? []))->map(fn ($id) => (string) $id);
            $inputType = $dimension->selection_mode === 'single' ? 'radio' : 'checkbox';
        @endphp
        <fieldset class="space-y-2.5">
            <legend class="w-full">
                <span class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech">
                    {{ $dimension->name }}
                    @if($dimension->is_required)<span class="text-error">*</span>@endif
                </span>
                <span class="block text-[11px] text-outline mt-0.5">
                    {{ $dimension->selection_mode_label }}{{ $dimension->description ? ' · '.$dimension->description : '' }}
                </span>
            </legend>

            <div class="flex flex-wrap gap-2">
                @foreach($dimension->categories as $term)
                <label class="cursor-pointer">
                    <input type="{{ $inputType }}"
                           name="classifications[{{ $dimension->id }}][]"
                           value="{{ $term->id }}"
                           class="peer sr-only"
                           {{ $oldSelection->contains((string) $term->id) ? 'checked' : '' }}>
                    <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-surface-container-high bg-surface-container-lowest text-xs text-on-surface-variant peer-checked:border-primary peer-checked:bg-primary-fixed peer-checked:text-primary peer-checked:font-bold hover:border-primary/40">
                        <span class="material-symbols-outlined text-sm">{{ $term->icon ?: 'label' }}</span>
                        {{ $term->path_label }}
                    </span>
                </label>
                @endforeach
            </div>

            @error("classifications.{$dimension->id}")
                <p class="text-xs text-error">{{ $message }}</p>
            @enderror
        </fieldset>
        @endforeach
    </div>
</section>
@endif
