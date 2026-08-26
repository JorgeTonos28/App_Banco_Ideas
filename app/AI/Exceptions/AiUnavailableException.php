<?php

namespace App\AI\Exceptions;

use RuntimeException;

class AiUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'El asistente de IA no está disponible en este momento.', public readonly string $errorCode = 'ai_unavailable')
    {
        parent::__construct($message);
    }
}
