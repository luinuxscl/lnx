<?php

namespace App\Ai\Sam;

/**
 * Brief about the record in focus, declared by a module.
 *
 * A brief is deterministic by default: the module composes it from its own
 * signals. The runtime may add a model narrative when the provider is
 * available; the fallback stays useful without a model call.
 *
 * @see SamSignal
 * @see SamAction
 */
final class SamBrief
{
    /**
     * @param  list<SamSignal>  $signals
     * @param  list<string>  $observations
     * @param  list<SamAction>  $actions
     */
    public function __construct(
        public readonly string $source,
        public readonly ?string $resource = null,
        public readonly ?string $id = null,
        public readonly ?string $label = null,
        public readonly ?string $resourceLabel = null,
        public readonly array $signals = [],
        public readonly array $observations = [],
        public readonly ?string $narrative = null,
        public readonly array $actions = [],
        public readonly ?string $evidence = null,
        public readonly ?string $continuePrompt = null,
        public readonly ?string $narrativeUrl = null,
    ) {}

    /**
     * A bounded, scalar-only representation safe to send to the provider.
     *
     * @return array{source: string, resource?: string, id?: string, label?: string, resource_label?: string, signals?: list<array{name: string, value: string|int|bool, evidence?: string}>, observations?: list<string>, narrative?: string, actions?: list<array{key: string, label: string, description: string}>, evidence?: string}
     */
    public function sanitized(): array
    {
        $brief = ['source' => $this->source];

        foreach (['resource', 'id', 'label', 'resourceLabel', 'narrative', 'evidence'] as $key) {
            $value = $this->{$key};

            if (is_string($value) && trim($value) !== '') {
                $brief[$key === 'resourceLabel' ? 'resource_label' : $key] = mb_substr(trim($value), 0, 200);
            }
        }

        $signals = [];

        foreach ($this->signals as $signal) {
            $signals[] = $signal->sanitized();
        }

        if ($signals !== []) {
            $brief['signals'] = $signals;
        }

        $observations = [];

        foreach ($this->observations as $observation) {
            if (is_string($observation) && trim($observation) !== '') {
                $observations[] = mb_substr(trim($observation), 0, 300);
            }
        }

        if ($observations !== []) {
            $brief['observations'] = $observations;
        }

        $actions = [];

        foreach ($this->actions as $action) {
            $actions[] = $action->sanitized();
        }

        if ($actions !== []) {
            $brief['actions'] = $actions;
        }

        return $brief;
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
