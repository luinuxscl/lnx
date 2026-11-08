<?php

namespace App\Ai\Sam;

use App\Models\User;

/**
 * Optional contributor capability for ambient proactive badges.
 *
 * Modules that implement this answer for a bounded list of record ids on the
 * current screen. The runtime caps the batch, caches per actor and record and
 * never infers observations on its own.
 */
interface SamAmbientSignals
{
    /**
     * High-confidence proactive observations for records on the current screen.
     *
     * @param  list<string>  $ids
     * @return list<SamRowSignals>
     */
    public function signalsForRows(User $actor, string $resource, array $ids): array;
}
