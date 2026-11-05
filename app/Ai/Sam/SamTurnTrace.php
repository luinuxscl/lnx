<?php

namespace App\Ai\Sam;

/**
 * What the agent read or proposed during one turn, declared by a module.
 *
 * It is deterministic and comes from the audit of the turn, never from the
 * model: the same correlation that joins the reads joins the trace. The
 * runtime only carries it; the module decides what the actor may see and
 * whether there is an activity screen to link.
 *
 * @see SamTraceEntry
 * @see SamTracePresenter
 */
final class SamTurnTrace
{
    /**
     * @param  list<SamTraceEntry>  $entries
     */
    public function __construct(
        public readonly string $source,
        public readonly string $correlationId,
        public readonly array $entries = [],
        public readonly ?string $activityUrl = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
