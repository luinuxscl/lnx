<?php

namespace App\Ai\Sam;

/**
 * Authority level the agent has over a module.
 *
 * The mode orders what Sam may do, not what it may see: reading is always
 * allowed when the surface permission is granted. A deployment sets a ceiling,
 * each role may lower it, and the person may lower it again: the effective
 * mode is the lowest of them.
 */
enum SamMode: string
{
    case ReadOnly = 'read_only';

    case Draft = 'draft';

    case Assisted = 'assisted';

    /**
     * Position of the mode in the authority scale.
     */
    public function rank(): int
    {
        return match ($this) {
            self::ReadOnly => 0,
            self::Draft => 1,
            self::Assisted => 2,
        };
    }

    /**
     * Whether this mode reaches the required one.
     */
    public function allows(self $required): bool
    {
        return $this->rank() >= $required->rank();
    }

    /**
     * The lowest of the two modes, used to apply a ceiling.
     */
    public function atMost(self $ceiling): self
    {
        return $this->rank() <= $ceiling->rank() ? $this : $ceiling;
    }

    /**
     * Parse a configured value, falling back to the conservative mode.
     */
    public static function parse(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (! is_string($value)) {
            return self::ReadOnly;
        }

        return self::tryFrom(trim($value)) ?? self::ReadOnly;
    }

    /**
     * Human-readable label, used in instructions and audit context.
     */
    public function label(): string
    {
        return match ($this) {
            self::ReadOnly => 'Read only',
            self::Draft => 'Draft',
            self::Assisted => 'Assisted',
        };
    }
}
