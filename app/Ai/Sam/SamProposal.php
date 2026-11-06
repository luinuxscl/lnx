<?php

namespace App\Ai\Sam;

use Carbon\CarbonInterface;

/**
 * A draft proposed by a module, as shown to the person and confirmed by them.
 *
 * It is a read model: the runtime never writes from it. The owning module
 * resolves it again by correlation before confirming, so a forged DTO cannot
 * confirm anything.
 *
 * The risk decides how it is confirmed. The phrase is required by default, so
 * a module that does not declare its risk never relaxes the confirmation.
 *
 * `canUndo` tells the surface whether the module still holds a ready undo for
 * the confirmed change; `undoExpiresAt` is its window, when it has one. A
 * module that cannot really reverse the change leaves both untouched, and the
 * panel never offers the button.
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
        public readonly SamRisk $risk = SamRisk::Reversible,
        public readonly bool $requiresPhrase = true,
        public readonly bool $canUndo = false,
        public readonly ?CarbonInterface $undoExpiresAt = null,
    ) {}
}
