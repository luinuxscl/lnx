<?php

namespace App\Support\Dashboard;

/**
 * A real time series: the host only accepts numeric points.
 */
final class SeriesData implements WidgetData
{
    /**
     * @param  list<int|float>  $series
     * @param  list<string>  $labels
     */
    public function __construct(
        public readonly array $series,
        public readonly array $labels = [],
        public readonly string $chart = 'line',
        public readonly ?string $value = null,
        public readonly ?string $change = null,
        public readonly ?string $href = null,
        public readonly string $color = 'accent',
    ) {}
}
