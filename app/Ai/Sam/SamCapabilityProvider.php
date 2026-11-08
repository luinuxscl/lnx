<?php

namespace App\Ai\Sam;

/**
 * Optional contract of the modules that declare capabilities for Sam.
 *
 * It is optional on purpose: an existing contributor keeps working without a
 * manifest, and `sam:doctor` reports it as pending migration instead of
 * breaking the turn. A module that declares capabilities becomes the single
 * source of truth for their permission, risk and confirmation.
 */
interface SamCapabilityProvider
{
    /**
     * Capabilities the module exposes to the surfaces.
     *
     * The declaration is static: it does not depend on the actor, because the
     * authorization is re-checked by every surface at invocation time.
     *
     * @return list<SamCapability>
     */
    public function capabilities(): array;
}
