<?php

namespace App\AI\Services;

use App\AI\DTO\AiProviderResult;
use App\Models\AiUsageLog;
use App\Models\User;

class AiUsageRecorder
{
    public function success(User $user, array $configuration, ?string $promptVersion, AiProviderResult $result, int $latencyMs, bool $escalated = false): void
    {
        AiUsageLog::create([
            'user_id' => $user->id,
            'feature' => $configuration['feature'],
            'provider' => $configuration['provider'],
            'model' => $configuration['model'],
            'prompt_version' => $promptVersion,
            'request_id' => $result->requestId,
            'success' => true,
            'escalated' => $escalated,
            'latency_ms' => $latencyMs,
            'input_units' => $result->inputUnits,
            'output_units' => $result->outputUnits,
            'estimated_cost_usd' => $this->estimateTextCost(
                $configuration['model'],
                $result->inputUnits,
                $result->outputUnits,
            ),
        ]);
    }

    public function failure(User $user, array $configuration, ?string $promptVersion, int $latencyMs, string $errorCode, bool $escalated = false): void
    {
        AiUsageLog::create([
            'user_id' => $user->id,
            'feature' => $configuration['feature'],
            'provider' => $configuration['provider'],
            'model' => $configuration['model'],
            'prompt_version' => $promptVersion,
            'success' => false,
            'escalated' => $escalated,
            'latency_ms' => $latencyMs,
            'error_code' => mb_substr($errorCode, 0, 255),
        ]);
    }

    private function estimateTextCost(string $model, ?int $inputUnits, ?int $outputUnits): ?float
    {
        $pricing = config('ai.pricing_per_million_units', [])[$model] ?? null;

        if (! is_array($pricing) || $inputUnits === null || $outputUnits === null) {
            return null;
        }

        return round(
            (($inputUnits * (float) $pricing['input']) + ($outputUnits * (float) $pricing['output'])) / 1_000_000,
            6,
        );
    }
}
