<?php

namespace App\AI\Services;

use App\AI\Contracts\IdeaOrganizerProvider;
use App\AI\Contracts\TranscriptionProvider;
use App\AI\Exceptions\AiConfigurationException;
use App\AI\Providers\OpenAiIdeaOrganizerProvider;
use App\AI\Providers\OpenAiTranscriptionProvider;
use Illuminate\Contracts\Container\Container;

class AiProviderRegistry
{
    public function __construct(private readonly Container $container) {}

    public function transcription(string $provider): TranscriptionProvider
    {
        return match ($provider) {
            'openai' => $this->container->make(OpenAiTranscriptionProvider::class),
            default => throw new AiConfigurationException('Proveedor de transcripción no soportado.'),
        };
    }

    public function organizer(string $provider): IdeaOrganizerProvider
    {
        return match ($provider) {
            'openai' => $this->container->make(OpenAiIdeaOrganizerProvider::class),
            default => throw new AiConfigurationException('Proveedor de organización no soportado.'),
        };
    }
}
