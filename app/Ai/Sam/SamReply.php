<?php

namespace App\Ai\Sam;

/**
 * Outcome of one Sam turn.
 */
final class SamReply
{
    public const OK = 'ok';

    public const FAILED = 'failed';

    /**
     * @param  list<array{type: string, title: string, url: string, slug?: string}>  $citations
     */
    public function __construct(
        public readonly string $status,
        public readonly string $text,
        public readonly ?string $correlationId = null,
        public readonly ?int $tokens = null,
        public readonly ?string $error = null,
        public readonly array $citations = [],
    ) {}

    /**
     * @param  list<array{type: string, title: string, url: string, slug?: string}>  $citations
     */
    public static function ok(string $text, string $correlationId, ?int $tokens = null, array $citations = []): self
    {
        return new self(self::OK, $text, $correlationId, $tokens, null, $citations);
    }

    public static function failed(string $error, ?string $correlationId = null): self
    {
        return new self(self::FAILED, '', $correlationId, null, $error);
    }

    public function succeeded(): bool
    {
        return $this->status === self::OK;
    }
}
