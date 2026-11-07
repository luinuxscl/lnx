<?php

namespace App\Support\Dashboard;

/**
 * The queue of drafts waiting for confirmation.
 */
final class ApprovalsData implements WidgetData
{
    /**
     * @param  list<ApprovalItem>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $href = null,
        public readonly ?string $empty = null,
    ) {}
}
