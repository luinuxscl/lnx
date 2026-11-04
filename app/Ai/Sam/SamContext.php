<?php

namespace App\Ai\Sam;

/**
 * Screen context declared by a module for a single turn.
 *
 * It carries identifiers and filters only: never form values, tokens, hashes
 * or free text taken from a record. The runtime sanitizes it again before it
 * reaches the provider.
 */
final class SamContext
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public readonly string $source,
        public readonly ?string $resource = null,
        public readonly ?string $id = null,
        public readonly ?string $label = null,
        public readonly ?string $route = null,
        public readonly array $filters = [],
    ) {}

    /**
     * A bounded, scalar-only representation safe to send to the provider.
     *
     * @return array{source: string, resource?: string, id?: string, label?: string, route?: string, filters?: array<string, bool|int|float|string>}
     */
    public function sanitized(): array
    {
        $context = ['source' => $this->source];

        foreach (['resource', 'id', 'label', 'route'] as $key) {
            $value = $this->{$key};

            if (is_string($value) && trim($value) !== '') {
                $context[$key] = mb_substr(trim($value), 0, 200);
            }
        }

        $filters = [];

        foreach ($this->filters as $key => $value) {
            if ($key === '') {
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value)) {
                $filters[$key] = $value;
            } elseif (is_string($value)) {
                $filters[$key] = mb_substr($value, 0, 200);
            }
        }

        if ($filters !== []) {
            $context['filters'] = $filters;
        }

        return $context;
    }

    /**
     * JSON payload injected into the system instructions.
     */
    public function toPrompt(): string
    {
        return (string) json_encode(
            $this->sanitized(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
}
