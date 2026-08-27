<?php

namespace App\AI\Services;

use App\AI\Contracts\IdeaOrganizerProvider;
use App\AI\Exceptions\AiConfigurationException;
use App\AI\Exceptions\AiProviderException;
use App\AI\Exceptions\AiUnavailableException;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Throwable;

class IdeaAiService
{
    public function __construct(
        private readonly AiConfigurationService $configuration,
        private readonly AiProviderRegistry $providers,
        private readonly IdeaAiContextBuilder $contextBuilder,
        private readonly AiPromptFactory $prompts,
        private readonly AiResponseValidator $validator,
        private readonly AiAmbiguityDetector $ambiguity,
        private readonly AiUsageRecorder $usage,
    ) {}

    public function transcribe(User $user, UploadedFile $audio): array
    {
        try {
            $configuration = $this->configuration->forFeature('transcription');
            $startedAt = hrtime(true);
            $result = $this->providers->transcription($configuration['provider'])->transcribe($audio, $configuration);
            $this->usage->success($user, $configuration, null, $result, $this->latency($startedAt));

            return $result->data;
        } catch (AiConfigurationException $exception) {
            throw new AiUnavailableException($exception->getMessage(), 'not_configured');
        } catch (AiProviderException $exception) {
            if (isset($configuration, $startedAt)) {
                $this->usage->failure($user, $configuration, null, $this->latency($startedAt), $exception->errorCode);
            }
            throw new AiUnavailableException(errorCode: $exception->errorCode);
        } catch (Throwable) {
            if (isset($configuration, $startedAt)) {
                $this->usage->failure($user, $configuration, null, $this->latency($startedAt), 'unexpected_error');
            }
            throw new AiUnavailableException;
        }
    }

    public function organize(User $user, array $draft, ?Idea $currentIdea = null): array
    {
        return $this->structured(
            user: $user,
            feature: 'idea_organization',
            promptVersion: AiPromptFactory::ORGANIZATION_VERSION,
            buildRequest: fn (array $context) => $this->prompts->organization($draft, $context, $this->safetyIdentifier($user)),
            validate: fn (array $data, array $context) => $this->validator->organization($data, $context),
            isAmbiguous: fn (array $result, float $threshold) => $this->ambiguity->organization($result, $threshold),
            currentIdea: $currentIdea,
        );
    }

    public function relations(User $user, array $draft, ?int $parentIdeaId, ?Idea $currentIdea = null): array
    {
        $context = $this->contextBuilder->build($user, $currentIdea);

        if ($context['allowed_idea_ids'] === []) {
            return ['relations' => [], 'confidence' => 1.0, '_meta' => ['escalated' => false]];
        }

        return $this->structured(
            user: $user,
            feature: 'idea_relations',
            promptVersion: AiPromptFactory::RELATIONS_VERSION,
            buildRequest: fn (array $builtContext) => $this->prompts->relations($draft, $parentIdeaId, $builtContext, $this->safetyIdentifier($user)),
            validate: fn (array $data, array $builtContext) => $this->validator->relations($data, $builtContext),
            isAmbiguous: fn (array $result, float $threshold) => $this->ambiguity->relations($result, $threshold),
            currentIdea: $currentIdea,
            suppliedContext: $context,
        );
    }

    private function structured(
        User $user,
        string $feature,
        string $promptVersion,
        callable $buildRequest,
        callable $validate,
        callable $isAmbiguous,
        ?Idea $currentIdea = null,
        ?array $suppliedContext = null,
    ): array {
        try {
            $configuration = $this->configuration->forFeature($feature);
            $context = $suppliedContext ?: $this->contextBuilder->build($user, $currentIdea);
            $request = $buildRequest($context);
            $provider = $this->providers->organizer($configuration['provider']);
            $result = $this->callOrganizer($provider, $request, $configuration, $user, $promptVersion, false);
            $escalated = false;
            $fallback = null;

            if (filled($configuration['fallback_model'])) {
                $fallback = $configuration;
                $fallback['model'] = $configuration['fallback_model'];
                $fallback['reasoning_effort'] = $configuration['fallback_reasoning_effort'];
            }

            try {
                $validated = $validate($result->data, $context);
            } catch (ValidationException $exception) {
                if (! $fallback) {
                    throw $exception;
                }

                $fallbackResult = $this->callOrganizer($provider, $request, $fallback, $user, $promptVersion, true);
                $validated = $validate($fallbackResult->data, $context);
                $escalated = true;
            }

            if (! $escalated && $isAmbiguous($validated, $configuration['ambiguity_threshold']) && $fallback) {
                $fallbackResult = $this->callOrganizer($provider, $request, $fallback, $user, $promptVersion, true);
                $validated = $validate($fallbackResult->data, $context);
                $escalated = true;
            }

            $validated['_meta'] = ['escalated' => $escalated, 'prompt_version' => $promptVersion];

            return $validated;
        } catch (AiConfigurationException $exception) {
            throw new AiUnavailableException($exception->getMessage(), 'not_configured');
        } catch (ValidationException) {
            throw new AiUnavailableException('La IA devolvió sugerencias que no pasaron los controles de seguridad.', 'invalid_structured_output');
        } catch (AiProviderException $exception) {
            throw new AiUnavailableException(errorCode: $exception->errorCode);
        } catch (Throwable) {
            throw new AiUnavailableException;
        }
    }

    private function callOrganizer(IdeaOrganizerProvider $provider, array $request, array $configuration, User $user, string $promptVersion, bool $escalated)
    {
        $startedAt = hrtime(true);

        try {
            $result = $provider->generate($request, $configuration);
            $this->usage->success($user, $configuration, $promptVersion, $result, $this->latency($startedAt), $escalated);

            return $result;
        } catch (AiProviderException $exception) {
            $this->usage->failure($user, $configuration, $promptVersion, $this->latency($startedAt), $exception->errorCode, $escalated);

            throw $exception;
        } catch (Throwable $exception) {
            $this->usage->failure($user, $configuration, $promptVersion, $this->latency($startedAt), 'unexpected_error', $escalated);

            throw $exception;
        }
    }

    private function safetyIdentifier(User $user): string
    {
        return substr(hash_hmac('sha256', (string) $user->id, (string) config('app.key')), 0, 64);
    }

    private function latency(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
