<?php

namespace Tests\Fixtures\Sam;

use App\Models\User;
use Luinuxscl\LnxSam\SamFilterAnalysis;
use Luinuxscl\LnxSam\SamListSignals;
use Luinuxscl\LnxSam\SamSignal;

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
