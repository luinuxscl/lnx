<?php

namespace App\Ai\Sam;

/**
 * How one person wants Sam to answer.
 *
 * They are stored by the product package that owns the surface, never by the
 * runtime: the kit only composes the instructions from them. The defaults are
 * deliberately conservative so a project without preferences gets the same
 * behavior as before.
 */
final class SamPreferences
{
    public const CONCISE = 'concise';

    public const DETAILED = 'detailed';

    public function __construct(
        public readonly string $verbosity = self::CONCISE,
        public readonly bool $showWhy = true,
        public readonly bool $showSources = true,
    ) {}

    public static function defaults(): self
    {
        return new self;
    }

    public function detailed(): bool
    {
        return $this->verbosity === self::DETAILED;
    }

    /**
     * A bounded, scalar-only representation safe to persist or send.
     *
     * @return array{verbosity: string, show_why: bool, show_sources: bool}
     */
    public function sanitized(): array
    {
        return [
            'verbosity' => $this->detailed() ? self::DETAILED : self::CONCISE,
            'show_why' => $this->showWhy,
            'show_sources' => $this->showSources,
        ];
    }
}
