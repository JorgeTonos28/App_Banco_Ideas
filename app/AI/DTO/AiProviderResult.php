<?php

namespace App\AI\DTO;

readonly class AiProviderResult
{
    public function __construct(
        public array $data,
        public ?string $requestId = null,
        public ?int $inputUnits = null,
        public ?int $outputUnits = null,
    ) {}
}
