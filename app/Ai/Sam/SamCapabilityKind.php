<?php

namespace App\Ai\Sam;

/**
 * Kind of operation a capability declares.
 *
 * A read never writes; a proposal only returns a draft. Anything that applies
 * the change is a person's action and never a capability of the agent.
 */
enum SamCapabilityKind: string
{
    case Read = 'read';

    case Propose = 'propose';

    /**
     * Human-readable label, used by the introspection commands.
     */
    public function label(): string
    {
        return match ($this) {
            self::Read => 'Read',
            self::Propose => 'Propose',
        };
    }
}
