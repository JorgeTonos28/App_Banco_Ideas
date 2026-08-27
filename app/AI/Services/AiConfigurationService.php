<?php

namespace App\AI\Services;

use App\AI\Exceptions\AiConfigurationException;
use App\Models\AiFeatureSetting;
use App\Models\AiProviderConfig;

class AiConfigurationService
{
    public function forFeature(string $feature): array
    {
        $defaults = config("ai.features.{$feature}");

        if (! is_array($defaults)) {
            throw new AiConfigurationException('La función de IA solicitada no está registrada.');
        }

        $setting = AiFeatureSetting::query()->where('feature', $feature)->first();
        $provider = $setting?->provider ?: $defaults['provider'];
        $providerDefaults = config("ai.providers.{$provider}");

        if (! is_array($providerDefaults)) {
            throw new AiConfigurationException('El proveedor de IA configurado no está permitido.');
        }

        $model = $setting?->model ?: $defaults['model'];
        $allowedModels = $defaults['allowed_models'] ?? [];

        if (! in_array($model, $allowedModels, true)) {
            throw new AiConfigurationException('El modelo configurado no está permitido para esta función.');
        }

        $providerConfig = AiProviderConfig::query()->where('provider', $provider)->first();
        $apiKey = $providerConfig?->api_key ?: ($providerDefaults['api_key'] ?? null);
        $providerEnabled = $providerConfig ? $providerConfig->enabled : filled($apiKey);
        $featureEnabled = $setting ? $setting->enabled : (bool) ($defaults['enabled'] ?? false);

        if (! $providerEnabled || ! $featureEnabled || blank($apiKey)) {
            throw new AiConfigurationException('La función de IA todavía no está habilitada por un administrador.');
        }

        $fallbackModel = $setting?->fallback_model ?: ($defaults['fallback_model'] ?? null);
        if ($fallbackModel && ! in_array($fallbackModel, $allowedModels, true)) {
            throw new AiConfigurationException('El modelo alternativo configurado no está permitido.');
        }

        return [
            'feature' => $feature,
            'provider' => $provider,
            'base_url' => rtrim($providerDefaults['base_url'], '/'),
            'api_key' => $apiKey,
            'model' => $model,
            'reasoning_effort' => $setting?->reasoning_effort ?: ($defaults['reasoning_effort'] ?? null),
            'fallback_model' => $fallbackModel,
            'fallback_reasoning_effort' => $setting?->fallback_reasoning_effort ?: ($defaults['fallback_reasoning_effort'] ?? null),
            'ambiguity_threshold' => (float) (($setting?->options['ambiguity_threshold'] ?? null) ?: ($defaults['ambiguity_threshold'] ?? 0.72)),
            'timeout_seconds' => (int) config('ai.timeout_seconds', 60),
        ];
    }
}
