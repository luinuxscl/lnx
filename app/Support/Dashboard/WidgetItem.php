<?php

namespace App\Support\Dashboard;

/**
 * One line of a list, an action group or a timeline.
 */
final class WidgetItem
{
    public function __construct(
        public readonly string $label,
        public readonly ?string $description = null,
        public readonly ?string $meta = null,
        public readonly ?string $href = null,
        public readonly ?string $prompt = null,
        public readonly ?string $icon = null,
        public readonly ?string $badge = null,
        public readonly string $color = 'accent',
    ) {}
}
