<?php

namespace App\AI\Providers;

use App\AI\Contracts\IdeaOrganizerProvider;
use App\AI\DTO\AiProviderResult;
use App\AI\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;

class OpenAiIdeaOrganizerProvider implements IdeaOrganizerProvider
{
    public function generate(array $request, array $configuration): AiProviderResult
    {
        $payload = [
            'model' => $configuration['model'],
            'instructions' => $request['instructions'],
            'input' => $request['input'],
            'store' => false,
            'max_output_tokens' => $request['max_output_tokens'] ?? 3000,
            'safety_identifier' => $request['safety_identifier'],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => $request['schema_name'],
                    'strict' => true,
                    'schema' => $request['schema'],
                ],
            ],
        ];

        if (filled($configuration['reasoning_effort'] ?? null)) {
            $payload['reasoning'] = ['effort' => $configuration['reasoning_effort']];
        }

        $response = Http::withToken($configuration['api_key'])
            ->acceptJson()
            ->timeout($configuration['timeout_seconds'])
            ->post($configuration['base_url'].'/responses', $payload);

        if (! $response->successful()) {
            throw new AiProviderException('El proveedor no pudo organizar la idea.', 'openai_http_'.$response->status());
        }

        if ($response->json('status') !== 'completed') {
            throw new AiProviderException('La respuesta del modelo no se completó.', 'incomplete_response');
        }

        $outputText = collect($response->json('output', []))
            ->flatMap(fn (array $item) => $item['content'] ?? [])
            ->firstWhere('type', 'output_text')['text'] ?? null;

        if (! is_string($outputText)) {
            $refusal = collect($response->json('output', []))
                ->flatMap(fn (array $item) => $item['content'] ?? [])
                ->firstWhere('type', 'refusal')['refusal'] ?? null;

            throw new AiProviderException(
                $refusal ? 'El proveedor rechazó procesar este contenido.' : 'El proveedor devolvió una respuesta sin datos.',
                $refusal ? 'model_refusal' : 'missing_output_text',
            );
        }

        $decoded = json_decode($outputText, true);
        if (! is_array($decoded)) {
            throw new AiProviderException('La respuesta estructurada no pudo interpretarse.', 'invalid_json');
        }

        return new AiProviderResult(
            data: $decoded,
            requestId: $response->header('x-request-id') ?: $response->json('id'),
            inputUnits: $response->json('usage.input_tokens'),
            outputUnits: $response->json('usage.output_tokens'),
        );
    }
}
