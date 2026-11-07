<?php

namespace App\Support\Dashboard;

/**
 * Recent events rendered with the kit timeline.
 */
final class TimelineData implements WidgetData
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
