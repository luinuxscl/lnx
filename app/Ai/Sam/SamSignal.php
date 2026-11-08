<?php

namespace App\Ai\Sam;

/**
 * One deterministic observation about a record, declared by a module.
 *
 * A signal is computed by the module in SQL, never by the model: `name` is a
 * stable identifier, `value` is the short finding and `evidence` links to the
 * screen where the person can check it.
 */
final class SamSignal
{
    public function __construct(
        public readonly string $name,
        public readonly string|int|bool $value,
        public readonly ?string $evidence = null,
        public readonly bool $proactive = false,
    ) {}

    /**
     * A bounded, scalar-only representation safe to send to the provider.
     *
     * @return array{name: string, value: string|int|bool, evidence?: string}
     */
    public function sanitized(): array
    {
        $signal = [
            'name' => mb_substr($this->name, 0, 60),
            'value' => is_string($this->value) ? mb_substr($this->value, 0, 200) : $this->value,
        ];

        if (is_string($this->evidence) && trim($this->evidence) !== '') {
            $signal['evidence'] = mb_substr(trim($this->evidence), 0, 300);
        }

        return $signal;
    }
}
