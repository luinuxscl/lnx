<?php

namespace App\Support\Dashboard;

/**
 * A short list: the host limits how many items it renders.
 */
final class ListData implements WidgetData
{
    /**
     * @param  list<WidgetItem>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $href = null,
        public readonly ?string $empty = null,
        public readonly int $limit = 5,
    ) {}
}
