<?php

namespace App\Ai\Sam\Tools;

use App\Actions\RecordMcpInvocation;
use App\Ai\Sam\SamTurn;
use App\Models\User;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\Tool;

/**
 * Base of every tool Sam may call.
 *
 * The runtime only accepts these two bases, so a module cannot slip a
 * confirmation, deletion, merge or send tool into the surface. Every call is
 * audited in the shared trail under the correlation of its turn.
 */
abstract class SamTool implements CanActAsTool, Tool
{
    public const TOOL_NAME = '';

    public const ACTION = 'tool';

    protected string $source = 'unknown';

    public function withSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function name(): string
    {
        return static::TOOL_NAME !== '' ? static::TOOL_NAME : class_basename(static::class);
    }

    /**
     * Record one invocation of this tool in the shared audit trail.
     *
     * @param  array<string, mixed>  $context
     */
    protected function audit(?User $actor, string $status, ?int $resultCount = null, array $context = []): void
    {
        app(RecordMcpInvocation::class)->handle(
            $actor,
            $this->name(),
            $status,
            SamTurn::correlationId(),
            $resultCount,
            array_merge([
                'channel' => 'sam',
                'source' => $this->source,
                'action' => static::ACTION,
            ], $context),
        );
    }

    protected function actor(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
