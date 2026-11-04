<?php

namespace App\Ai\Sam;

/**
 * Outcome of a confirmation delegated to the owning module.
 *
 * `duplicate`, `failed` and `expired` are never presented as success.
 */
final class SamProposalResult
{
    public const CONFIRMED = 'confirmed';

    public const DUPLICATE = 'duplicate';

    public const FAILED = 'failed';

    public const EXPIRED = 'expired';

    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly ?string $recordUrl = null,
    ) {}

    public static function confirmed(string $message, ?string $recordUrl = null): self
    {
        return new self(self::CONFIRMED, $message, $recordUrl);
    }

    public static function duplicate(string $message, ?string $recordUrl = null): self
    {
        return new self(self::DUPLICATE, $message, $recordUrl);
    }

    public static function failed(string $message, ?string $recordUrl = null): self
    {
        return new self(self::FAILED, $message, $recordUrl);
    }

    public static function expired(string $message, ?string $recordUrl = null): self
    {
        return new self(self::EXPIRED, $message, $recordUrl);
    }

    public function succeeded(): bool
    {
        return $this->status === self::CONFIRMED;
    }
}
