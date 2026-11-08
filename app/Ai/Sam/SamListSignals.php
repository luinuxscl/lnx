<?php

namespace App\Ai\Sam;

use App\Models\User;

/**
 * Optional contributor capability for aggregate list-filter analysis.
 *
 * Modules answer with bounded SQL over the same filters the list screen uses.
 * The runtime never infers observations on its own.
 */
interface SamListSignals
{
    /**
     * High-confidence counts for records that match the list filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function signalsForFilter(User $actor, string $resource, array $filters): ?SamFilterAnalysis;
}
