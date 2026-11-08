<?php

namespace Tests\Fixtures\Sam;

use App\Ai\Sam\SamFilterAnalysis;
use App\Ai\Sam\SamListSignals;
use App\Ai\Sam\SamSignal;
use App\Models\User;

class ListFilterFakeSamContributor extends FakeSamContributor implements SamListSignals
{
    public static int $calls = 0;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function signalsForFilter(User $actor, string $resource, array $filters): ?SamFilterAnalysis
    {
        self::$calls++;

        if ($resource !== 'contact') {
            return null;
        }

        return new SamFilterAnalysis(
            signals: [new SamSignal('missing_company', 3)],
            truncated: false,
            matchedRows: 10,
        );
    }
}
