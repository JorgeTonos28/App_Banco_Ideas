<?php

namespace App\AI\Contracts;

use App\AI\DTO\AiProviderResult;
use Illuminate\Http\UploadedFile;

interface TranscriptionProvider
{
    public function transcribe(UploadedFile $audio, array $configuration): AiProviderResult;
}
