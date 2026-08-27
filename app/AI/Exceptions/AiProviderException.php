<?php

namespace App\AI\Exceptions;

use RuntimeException;

class AiProviderException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode = 'provider_error')
    {
        parent::__construct($message);
    }
}
