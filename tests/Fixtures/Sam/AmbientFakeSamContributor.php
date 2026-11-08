<?php

namespace Tests\Fixtures\Sam;

use App\Ai\Sam\SamAmbientSignals;
use App\Ai\Sam\SamRowSignals;
use App\Ai\Sam\SamSignal;
use App\Models\User;

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
