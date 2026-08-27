<?php

namespace App\Http\Controllers\Admin;

use App\AI\Exceptions\AiConfigurationException;
use App\AI\Exceptions\AiProviderException;
use App\AI\Services\AiConfigurationService;
use App\AI\Services\OpenAiConnectionTestService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAiSettingsRequest;
use App\Models\AiFeatureSetting;
use App\Models\AiProviderConfig;
use App\Models\AiUsageLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class AdminAiSettingsController extends Controller
{
    public function index(): View
    {
        $provider = AiProviderConfig::query()->where('provider', 'openai')->first();
        $storedSettings = AiFeatureSetting::query()->get()->keyBy('feature');
        $features = collect(config('ai.features'))->map(function (array $defaults, string $feature) use ($storedSettings): array {
            $stored = $storedSettings->get($feature);

            return array_merge($defaults, [
                'key' => $feature,
                'model' => $stored?->model ?: $defaults['model'],
                'reasoning_effort' => $stored?->reasoning_effort ?: ($defaults['reasoning_effort'] ?? null),
                'fallback_model' => $stored?->fallback_model ?: ($defaults['fallback_model'] ?? null),
                'fallback_reasoning_effort' => $stored?->fallback_reasoning_effort ?: ($defaults['fallback_reasoning_effort'] ?? null),
                'enabled' => $stored ? $stored->enabled : $defaults['enabled'],
                'ambiguity_threshold' => $stored?->options['ambiguity_threshold'] ?? ($defaults['ambiguity_threshold'] ?? null),
            ]);
        });

        $usage = AiUsageLog::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('COUNT(*) as requests, SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successes, SUM(CASE WHEN escalated = 1 THEN 1 ELSE 0 END) as escalations')
            ->first();

        return view('admin.ai.index', compact('provider', 'features', 'usage'));
    }

    public function update(UpdateAiSettingsRequest $request): RedirectResponse
    {
        $existingProvider = AiProviderConfig::query()->where('provider', 'openai')->first();
        if ($request->boolean('provider_enabled')
            && ! $request->filled('api_key')
            && blank($existingProvider?->api_key)
            && blank(config('ai.providers.openai.api_key'))) {
            return back()->withInput()->with('error', 'Guarda una clave de API antes de habilitar OpenAI.');
        }

        $provider = DB::transaction(function () use ($request): AiProviderConfig {
            $provider = AiProviderConfig::query()->firstOrNew(['provider' => 'openai']);

            if ($request->filled('api_key')) {
                $provider->api_key = trim($request->string('api_key')->toString());
            }

            $provider->enabled = $request->boolean('provider_enabled');
            $provider->configured_by_user_id = $request->user()->id;
            $provider->save();

            foreach (config('ai.features') as $feature => $defaults) {
                $input = $request->input("features.{$feature}");
                $allowedModels = $defaults['allowed_models'];

                if (! is_array($input) || ! in_array($input['model'], $allowedModels, true)) {
                    continue;
                }

                AiFeatureSetting::updateOrCreate(['feature' => $feature], [
                    'provider' => 'openai',
                    'model' => $input['model'],
                    'reasoning_effort' => $input['reasoning_effort'] ?? null,
                    'fallback_model' => filled($input['fallback_model'] ?? null) ? $input['fallback_model'] : null,
                    'fallback_reasoning_effort' => $input['fallback_reasoning_effort'] ?? null,
                    'enabled' => filter_var($input['enabled'] ?? false, FILTER_VALIDATE_BOOL),
                    'options' => isset($input['ambiguity_threshold']) ? ['ambiguity_threshold' => (float) $input['ambiguity_threshold']] : null,
                ]);
            }

            return $provider;
        });

        return back()->with('success', 'Configuración de IA actualizada.');
    }

    public function test(AiConfigurationService $configuration, OpenAiConnectionTestService $tester): RedirectResponse
    {
        try {
            $settings = $configuration->forFeature('transcription');
            $tester->test($settings);
            AiProviderConfig::query()->where('provider', 'openai')->update(['last_tested_at' => now()]);

            return back()->with('success', 'Conexión con OpenAI verificada correctamente.');
        } catch (AiConfigurationException|AiProviderException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable) {
            return back()->with('error', 'No fue posible completar la prueba de conexión.');
        }
    }
}
