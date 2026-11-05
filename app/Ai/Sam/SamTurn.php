<?php

namespace App\Ai\Sam;

use Illuminate\Support\Str;

/**
 * Correlation shared by every read, proposal and confirmation of one turn.
 *
 * The runtime opens the turn before calling the provider, so the tools of the
 * same turn audit under the same id regardless of the order they run in. The
 * id is published under the MCP attribute too, because the read and write
 * tools audit through it: without it a tool invoked inside a turn would open
 * its own random correlation and the turn trace could not join its reads.
 */
final class SamTurn
{
    public const ATTRIBUTE = 'lnx.sam.correlation_id';

    public const MCP_ATTRIBUTE = 'mcp.correlation_id';

    public static function start(?string $correlationId = null): string
    {
        $correlationId = is_string($correlationId) && trim($correlationId) !== ''
            ? trim($correlationId)
            : (string) Str::uuid();

        request()->attributes->set(self::ATTRIBUTE, $correlationId);
        request()->attributes->set(self::MCP_ATTRIBUTE, $correlationId);

        return $correlationId;
    }

    public static function current(): ?string
    {
        $correlationId = request()->attributes->get(self::ATTRIBUTE);

        return is_string($correlationId) && $correlationId !== '' ? $correlationId : null;
    }

    public static function correlationId(): string
    {
        return self::current() ?? self::start();
    }

    public static function clear(): void
    {
        request()->attributes->remove(self::ATTRIBUTE);
        request()->attributes->remove(self::MCP_ATTRIBUTE);
    }
}
