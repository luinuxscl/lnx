<?php

namespace App\Ai\Sam;

/**
 * Proactive observations for one record on the current screen.
 *
 * Modules compute these in SQL for a bounded batch of ids. The runtime may
 * cache them per actor and record without ever sharing state between actors.
 */
final class SamRowSignals
{
    /**
     * @param  list<SamSignal>  $signals
     */
    public function __construct(
        public readonly string $id,
        public readonly array $signals,
        public readonly ?string $updatedAt = null,
    ) {}
}
