<?php

namespace App\Ai\Sam;

/**
 * One action the person can take from a brief or a row insight.
 *
 * It points to an existing screen: the module never executes the action from
 * the DTO. The URL is already authorized for the actor that received it.
 */
final class SamAction
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $description,
        public readonly string $url,
    ) {}

    /**
     * A bounded representation for the provider prompt: the URL is omitted.
     *
     * @return array{key: string, label: string, description: string}
     */
    public function sanitized(): array
    {
        return [
            'key' => mb_substr($this->key, 0, 60),
            'label' => mb_substr($this->label, 0, 120),
            'description' => mb_substr($this->description, 0, 200),
        ];
    }
}
