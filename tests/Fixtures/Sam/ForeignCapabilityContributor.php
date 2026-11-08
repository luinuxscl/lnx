<?php

namespace Tests\Fixtures\Sam;

use App\Ai\Sam\SamCapability;
use App\Ai\Sam\SamCapabilityProvider;

/**
 * Contributor registered as `fake` that declares a capability of another
 * module: the registry must reject it instead of trusting the manifest.
 */
class ForeignCapabilityContributor extends FakeSamContributor implements SamCapabilityProvider
{
    /**
     * @return list<SamCapability>
     */
    public function capabilities(): array
    {
        return [SamCapability::read('other.read', 'other', 'Foreign capability')];
    }
}
