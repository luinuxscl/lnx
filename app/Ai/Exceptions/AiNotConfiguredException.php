<?php

namespace App\Ai\Exceptions;

use RuntimeException;

class AiNotConfiguredException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct("AI is not configured: {$reason}.");
    }
}
