<?php

namespace App\AI\Services;

use App\AI\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;

class OpenAiConnectionTestService
{
    public function test(array $configuration): void
    {
        $response = Http::withToken($configuration['api_key'])
            ->acceptJson()
            ->timeout(min(20, $configuration['timeout_seconds']))
            ->get($configuration['base_url'].'/models/'.rawurlencode($configuration['model']));

        if (! $response->successful()) {
            throw new AiProviderException('OpenAI rechazó la prueba de conexión.', 'openai_http_'.$response->status());
        }
    }
}
