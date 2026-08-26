<?php

namespace App\AI\Providers;

use App\AI\Contracts\TranscriptionProvider;
use App\AI\DTO\AiProviderResult;
use App\AI\Exceptions\AiProviderException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class OpenAiTranscriptionProvider implements TranscriptionProvider
{
    public function transcribe(UploadedFile $audio, array $configuration): AiProviderResult
    {
        $stream = fopen($audio->getRealPath(), 'rb');

        if ($stream === false) {
            throw new AiProviderException('No se pudo leer el audio temporal.', 'audio_unreadable');
        }

        try {
            $response = Http::withToken($configuration['api_key'])
                ->acceptJson()
                ->timeout($configuration['timeout_seconds'])
                ->attach('file', $stream, $audio->getClientOriginalName())
                ->post($configuration['base_url'].'/audio/transcriptions', [
                    'model' => $configuration['model'],
                    'language' => 'es',
                    'prompt' => 'Idea de innovación institucional. Conserva siglas, nombres propios y términos técnicos.',
                ]);
        } finally {
            fclose($stream);
        }

        if (! $response->successful()) {
            throw new AiProviderException('El proveedor no pudo transcribir el audio.', 'openai_http_'.$response->status());
        }

        $text = trim((string) $response->json('text'));
        if ($text === '') {
            throw new AiProviderException('La transcripción recibida está vacía.', 'empty_transcription');
        }

        return new AiProviderResult(
            data: ['transcript' => $text],
            requestId: $response->header('x-request-id'),
        );
    }
}
