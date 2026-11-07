<?php

namespace App\Support\Dashboard;

/**
 * A single figure with an optional delta, sparkline and destination.
 */
final class StatData implements WidgetData
{
    /**
     * @param  list<int|float>  $series
     */
    public function __construct(
        public readonly string $value,
        public readonly ?string $label = null,
        public readonly ?string $delta = null,
        public readonly ?string $deltaTone = null,
        public readonly ?string $href = null,
        public readonly string $icon = 'chart-bar',
        public readonly string $color = 'accent',
        public readonly array $series = [],
    ) {}
}
