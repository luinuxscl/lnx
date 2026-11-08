<?php

namespace Tests\Fixtures\Sam;

use App\Ai\Sam\SamCapability;
use App\Ai\Sam\SamCapabilityProvider;

/**
 * Contributor that declares the same key twice: a capability is global and the
 * registry must refuse the ambiguity.
 */
class DuplicateCapabilityContributor extends FakeSamContributor implements SamCapabilityProvider
{
    /**
     * @return list<SamCapability>
     */
    public function capabilities(): array
    {
        return [
            SamCapability::read('fake.read', 'fake', 'Read fake records'),
            SamCapability::read('fake.read', 'fake', 'Read fake records again'),
        ];
    }
}
