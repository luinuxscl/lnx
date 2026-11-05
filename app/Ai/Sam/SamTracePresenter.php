<?php

namespace App\Ai\Sam;

use App\Models\User;

/**
 * Module side of the turn trace.
 *
 * The module reads its own audit for the given correlation, resolves the
 * labels the person already knows and answers null when the actor cannot see
 * that turn. The runtime never queries the audit on its own.
 *
 * @see SamTurnTrace
 */
interface SamTracePresenter
{
    /**
     * Trace of the turn, or null when the actor cannot see it.
     */
    public function trace(User $actor, string $correlationId): ?SamTurnTrace;
}
