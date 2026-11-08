<?php

namespace App\Ai\Sam;

/**
 * Deterministic aggregate signals for the current list filter.
 *
 * Modules compute counts in SQL over the filtered query, never over the whole
 * dataset. The runtime may cache the result per actor and filter fingerprint.
 */
final class SamFilterAnalysis
{
    /**
     * @param  list<SamSignal>  $signals
     */
    public function __construct(
        public readonly array $signals,
        public readonly bool $truncated = false,
        public readonly int $matchedRows = 0,
    ) {}
}
