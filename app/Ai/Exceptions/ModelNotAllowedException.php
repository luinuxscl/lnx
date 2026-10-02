<?php

namespace App\Ai\Exceptions;

use RuntimeException;

class ModelNotAllowedException extends RuntimeException
{
    public function __construct(public readonly string $model)
    {
        parent::__construct("The model [{$model}] is not in the server-side allowlist.");
    }
}
