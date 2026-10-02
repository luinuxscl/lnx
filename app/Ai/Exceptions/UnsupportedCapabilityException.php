<?php

namespace App\Ai\Exceptions;

use RuntimeException;

class UnsupportedCapabilityException extends RuntimeException
{
    /**
     * @param  list<string>  $capabilities
     */
    public function __construct(public readonly string $model, public readonly array $capabilities)
    {
        parent::__construct(
            "The model [{$this->model}] does not support: ".implode(', ', $this->capabilities).'.'
        );
    }
}
