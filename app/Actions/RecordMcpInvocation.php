<?php

namespace App\Actions;

use App\Models\McpToolInvocation;
use App\Models\User;

class RecordMcpInvocation
{
    /**
     * Record an MCP tool invocation for auditing.
     *
     * Only structural, non-sensitive context is stored. Free-text search terms
     * and tool results are never persisted.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(
        ?User $actor,
        string $tool,
        string $status,
        string $correlationId,
        ?int $resultCount = null,
        array $context = [],
    ): McpToolInvocation {
        return McpToolInvocation::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
            'tool' => $tool,
            'correlation_id' => $correlationId,
            'status' => $status,
            'result_count' => $resultCount,
            'context' => $context === [] ? null : $context,
            'recorded_at' => now(),
        ]);
    }
}
