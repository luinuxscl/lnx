<?php

namespace App\Ai\Sam;

use Carbon\CarbonInterface;

/**
 * A draft proposed by a module, as shown to the person and confirmed by them.
 *
 * It is a read model: the runtime never writes from it. The owning module
 * resolves it again by correlation before confirming, so a forged DTO cannot
 * confirm anything.
 */
final class SamProposal
{
    /**
     * @param  list<array{field: string, label: string, old: string, new: string}>  $changes
     */
    public function __construct(
        public readonly string $source,
        public readonly string $id,
        public readonly string $correlationId,
        public readonly string $title,
        public readonly string $summary,
        public readonly array $changes,
        public readonly string $status,
        public readonly ?CarbonInterface $expiresAt = null,
        public readonly ?string $recordUrl = null,
        public readonly bool $canConfirm = false,
    ) {}
}
