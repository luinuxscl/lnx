<?php

namespace App\Ai\Sam;

/**
 * Deterministic insight of one focused row, declared by a module.
 *
 * It answers what the person sees and what they can do next, without asking
 * the model. The module owns the observations and the actions; the runtime and
 * the UI only render them.
 *
 * @see SamSignal
 * @see SamAction
 * @see SamBrief
 */
final class SamInsight
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
        public readonly array $actions = [],
        public readonly ?string $continuePrompt = null,
        public readonly ?string $continueUrl = null,
        public readonly ?string $deepenUrl = null,
    ) {}

    /**
     * A bounded, scalar-only representation safe to send to the provider.
     *
     * @return array{source: string, resource?: string, id?: string, label?: string, resource_label?: string, signals?: list<array{name: string, value: string|int|bool, evidence?: string}>, observations?: list<string>, actions?: list<array{key: string, label: string, description: string}>, continue_url?: string, deepen_url?: string}
     */
    public function sanitized(): array
    {
        $insight = ['source' => $this->source];

        foreach (['resource', 'id', 'label', 'resourceLabel'] as $key) {
            $value = $this->{$key};

            if (is_string($value) && trim($value) !== '') {
                $insight[$key === 'resourceLabel' ? 'resource_label' : $key] = mb_substr(trim($value), 0, 200);
            }
        }

        $signals = [];

        foreach ($this->signals as $signal) {
            $signals[] = $signal->sanitized();
        }

        if ($signals !== []) {
            $insight['signals'] = $signals;
        }

        $observations = [];

        foreach ($this->observations as $observation) {
            if (is_string($observation) && trim($observation) !== '') {
                $observations[] = mb_substr(trim($observation), 0, 300);
            }
        }

        if ($observations !== []) {
            $insight['observations'] = $observations;
        }

        $actions = [];

        foreach ($this->actions as $action) {
            $actions[] = $action->sanitized();
        }

        if ($actions !== []) {
            $insight['actions'] = $actions;
        }

        foreach (['continueUrl', 'deepenUrl'] as $key) {
            $value = $this->{$key};

            if (is_string($value) && trim($value) !== '') {
                $insight[$key === 'continueUrl' ? 'continue_url' : 'deepen_url'] = mb_substr(trim($value), 0, 300);
            }
        }

        return $insight;
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
