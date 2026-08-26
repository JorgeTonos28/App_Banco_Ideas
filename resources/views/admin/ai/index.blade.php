@extends('layouts.app')

@section('title', 'Inteligencia artificial - Administración')

@section('content')
<div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <x-admin-nav-tabs />

    <div>
        <h1 class="font-headline text-2xl font-bold text-on-surface">Inteligencia artificial</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Configura la captura por voz y las sugerencias. La clave se cifra con la clave de la aplicación y nunca vuelve a mostrarse.</p>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-error/25 bg-error-container p-4 text-sm text-on-error-container">{{ session('error') }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4"><p class="text-xs text-outline">Solicitudes · 30 días</p><p class="mt-1 text-2xl font-bold">{{ (int) ($usage?->requests ?? 0) }}</p></div>
        <div class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4"><p class="text-xs text-outline">Exitosas</p><p class="mt-1 text-2xl font-bold">{{ (int) ($usage?->successes ?? 0) }}</p></div>
        <div class="rounded-2xl border border-surface-container-high bg-surface-container-lowest p-4"><p class="text-xs text-outline">Escalamientos</p><p class="mt-1 text-2xl font-bold">{{ (int) ($usage?->escalations ?? 0) }}</p></div>
    </div>

    <form method="POST" action="{{ route('admin.ai.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-3xl border border-surface-container-high bg-surface-container-lowest p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="font-headline text-lg font-bold">Proveedor OpenAI</h2><p class="text-xs text-on-surface-variant">Las URLs son fijas para impedir conexiones a destinos no autorizados.</p></div>
                <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="provider_enabled" value="1" {{ old('provider_enabled', $provider?->enabled) ? 'checked' : '' }} class="rounded border-outline text-primary"> Habilitado</label>
            </div>
            <div class="mt-5">
                <label for="api_key" class="mb-2 block text-xs font-bold uppercase tracking-wider">Clave de API</label>
                <input id="api_key" name="api_key" type="password" autocomplete="new-password" placeholder="{{ $provider?->api_key ? 'Configurada · deja vacío para conservarla' : 'Pega una clave de proyecto' }}" class="w-full rounded-2xl border border-surface-container-high bg-surface-container-low p-3 text-sm">
                @error('api_key')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                @if($provider?->last_tested_at)<p class="mt-2 text-xs text-on-surface-variant">Última prueba: {{ $provider->last_tested_at->format('d/m/Y H:i') }}</p>@endif
            </div>
        </section>

        <section class="space-y-4">
            @foreach($features as $feature)
                <div class="rounded-3xl border border-surface-container-high bg-surface-container-lowest p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div><h3 class="font-headline font-bold">{{ $feature['label'] }}</h3><p class="text-xs text-on-surface-variant">{{ $feature['key'] }}</p></div>
                        <label class="flex items-center gap-2 text-xs font-semibold"><input type="checkbox" name="features[{{ $feature['key'] }}][enabled]" value="1" {{ old("features.{$feature['key']}.enabled", $feature['enabled']) ? 'checked' : '' }} class="rounded border-outline text-primary"> Activa</label>
                    </div>
                    <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <label class="text-xs font-bold">Modelo
                            <select name="features[{{ $feature['key'] }}][model]" class="mt-1 w-full rounded-xl border border-surface-container-high bg-surface-container-low p-2.5 text-sm">
                                @foreach($feature['allowed_models'] as $model)<option value="{{ $model }}" {{ old("features.{$feature['key']}.model", $feature['model']) === $model ? 'selected' : '' }}>{{ $model }}</option>@endforeach
                            </select>
                        </label>
                        @if($feature['reasoning_effort'] !== null)
                            <label class="text-xs font-bold">Razonamiento
                                <select name="features[{{ $feature['key'] }}][reasoning_effort]" class="mt-1 w-full rounded-xl border border-surface-container-high bg-surface-container-low p-2.5 text-sm">
                                    @foreach(['none','low','medium','high','xhigh','max'] as $effort)<option value="{{ $effort }}" {{ old("features.{$feature['key']}.reasoning_effort", $feature['reasoning_effort']) === $effort ? 'selected' : '' }}>{{ $effort }}</option>@endforeach
                                </select>
                            </label>
                            <label class="text-xs font-bold">Modelo alternativo
                                <select name="features[{{ $feature['key'] }}][fallback_model]" class="mt-1 w-full rounded-xl border border-surface-container-high bg-surface-container-low p-2.5 text-sm">
                                    <option value="">Sin escalamiento</option>
                                    @foreach($feature['allowed_models'] as $model)<option value="{{ $model }}" {{ old("features.{$feature['key']}.fallback_model", $feature['fallback_model']) === $model ? 'selected' : '' }}>{{ $model }}</option>@endforeach
                                </select>
                            </label>
                            <label class="text-xs font-bold">Umbral de ambigüedad
                                <input type="number" min="0.5" max="0.95" step="0.01" name="features[{{ $feature['key'] }}][ambiguity_threshold]" value="{{ old("features.{$feature['key']}.ambiguity_threshold", $feature['ambiguity_threshold']) }}" class="mt-1 w-full rounded-xl border border-surface-container-high bg-surface-container-low p-2.5 text-sm">
                                <input type="hidden" name="features[{{ $feature['key'] }}][fallback_reasoning_effort]" value="{{ $feature['fallback_reasoning_effort'] }}">
                            </label>
                        @else
                            <input type="hidden" name="features[{{ $feature['key'] }}][reasoning_effort]" value="">
                            <input type="hidden" name="features[{{ $feature['key'] }}][fallback_model]" value="">
                            <input type="hidden" name="features[{{ $feature['key'] }}][fallback_reasoning_effort]" value="">
                        @endif
                    </div>
                </div>
            @endforeach
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white">Guardar configuración</button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.ai.test') }}" class="flex justify-end">@csrf<button class="rounded-xl border border-primary/30 px-5 py-2.5 text-sm font-bold text-primary">Probar conexión guardada</button></form>
</div>
@endsection
