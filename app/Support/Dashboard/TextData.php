<?php

namespace App\Support\Dashboard;

/**
 * A short piece of text, such as the deterministic brief of the agent.
 */
final class TextData implements WidgetData
{
    /**
     * @param  list<WidgetItem>  $actions
     */
    public function __construct(
        public readonly ?string $body = null,
        public readonly array $actions = [],
        public readonly ?string $href = null,
    ) {}
}
