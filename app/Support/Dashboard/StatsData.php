<?php

namespace App\Support\Dashboard;

/**
 * A group of figures rendered inside one statistics panel.
 */
final class StatsData implements WidgetData
{
    /**
     * @param  list<StatData>  $items
     * @param  list<WidgetItem>  $actions
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $href = null,
        public readonly ?string $empty = null,
        public readonly array $actions = [],
        public readonly ?string $description = null,
    ) {}
}
