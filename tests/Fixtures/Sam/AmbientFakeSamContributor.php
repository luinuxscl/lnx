<?php

namespace Tests\Fixtures\Sam;

use App\Models\User;
use Luinuxscl\LnxSam\SamAmbientSignals;
use Luinuxscl\LnxSam\SamRowSignals;
use Luinuxscl\LnxSam\SamSignal;

class AmbientFakeSamContributor extends FakeSamContributor implements SamAmbientSignals
{
    /** @var list<string> */
    public static array $lastIds = [];

    public static int $signalsForRowsCalls = 0;

    public function ambientRowVersions(User $actor, string $resource, array $ids): array
    {
        $versions = [];

        foreach ($ids as $id) {
            $versions[(string) $id] = '2026-11-08T00:00:00Z';
        }

        return $versions;
    }

    public function signalsForRows(User $actor, string $resource, array $ids): array
    {
        self::$lastIds = $ids;
        self::$signalsForRowsCalls++;

        if ($resource !== 'contact' || $ids === []) {
            return [];
        }

        return [
            new SamRowSignals(
                $ids[0],
                [new SamSignal('duplicate_email', 1, null, proactive: true)],
                updatedAt: '2026-11-08T00:00:00Z',
            ),
        ];
    }
}
