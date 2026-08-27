<?php

namespace App\AI\Contracts;

use App\AI\DTO\AiProviderResult;

interface IdeaOrganizerProvider
{
    public function generate(array $request, array $configuration): AiProviderResult;
}
