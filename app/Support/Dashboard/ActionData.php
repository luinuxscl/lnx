<?php

namespace App\Support\Dashboard;

/**
 * Shortcuts to screens or actions.
 */
final class ActionData implements WidgetData
{
    /**
     * @param  list<WidgetItem>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $href = null,
        public readonly ?string $empty = null,
    ) {}
}
