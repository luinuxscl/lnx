<?php

namespace App\Support\Dashboard;

/**
 * One draft waiting for a human decision.
 *
 * The module owns the record and the reject route; the dashboard only links to
 * them, so the phrase is still typed on the module's own screen.
 */
final class ApprovalItem
{
    /**
     * @param  list<string>  $changes
     */
    public function __construct(
        public readonly string $id,
        public readonly string $source,
        public readonly string $title,
        public readonly ?string $summary = null,
        public readonly array $changes = [],
        public readonly ?string $risk = null,
        public readonly bool $requiresPhrase = false,
        public readonly ?string $recordUrl = null,
        public readonly ?string $rejectUrl = null,
        public readonly bool $canReject = false,
        public readonly ?string $expiresAt = null,
    ) {}
}
