<?php

namespace App\Ai\Sam;

/**
 * Risk of a draft proposed by a module.
 *
 * The risk decides how the person confirms it: a reversible change is a normal
 * confirmation, while an irreversible or bulk change asks for the one-time
 * phrase. The module declares the risk; the runtime only applies the policy.
 */
enum SamRisk: string
{
    case Reversible = 'reversible';

    case Irreversible = 'irreversible';

    case Bulk = 'bulk';

    /**
     * Whether confirming the draft requires the one-time phrase.
     */
    public function requiresPhrase(): bool
    {
        return $this !== self::Reversible;
    }

    /**
     * Human-readable label, used in instructions and audit context.
     */
    public function label(): string
    {
        return match ($this) {
            self::Reversible => 'Reversible',
            self::Irreversible => 'Irreversible',
            self::Bulk => 'Bulk',
        };
    }
}
