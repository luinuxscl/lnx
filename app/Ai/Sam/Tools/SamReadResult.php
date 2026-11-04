<?php

namespace App\Ai\Sam\Tools;

/**
 * Data returned by a Sam read tool.
 *
 * Only the payload plus structural metadata is returned: the audit never
 * stores the payload itself.
 */
final class SamReadResult
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly array $data,
        public readonly ?string $resource = null,
        public readonly ?string $targetId = null,
        public readonly ?string $targetLabel = null,
        public readonly int $count = 0,
    ) {}

    public function toJson(): string
    {
        return (string) json_encode(
            $this->data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
}
