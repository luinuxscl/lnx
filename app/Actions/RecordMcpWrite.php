<?php

namespace App\Actions;

use App\Models\McpWriteOperation;
use App\Models\User;

class RecordMcpWrite
{
    /**
     * Find a previous write operation by its idempotency key.
     *
     * Used to replay an already executed write without touching the domain
     * again, so repeating a call with the same key never duplicates data.
     */
    public function find(User $actor, string $tool, string $idempotencyKey): ?McpWriteOperation
    {
        return McpWriteOperation::query()
            ->where('actor_id', $actor->id)
            ->where('tool', $tool)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    /**
     * Record a write operation, including the diff of what changed.
     *
     * The diff holds the previous and new value of each written field; it is
     * only persisted when the deployment enables it (`mcp_server.audit.diff`)
     * and, like the rest of the record, never leaves the database.
     *
     * @param  array<string, array{old: mixed, new: mixed}>|null  $diff
     * @param  array<string, mixed>|null  $response
     */
    public function handle(
        ?User $actor,
        string $tool,
        string $resource,
        string $operation,
        string $status,
        string $correlationId,
        ?string $resourceId = null,
        ?string $idempotencyKey = null,
        ?string $requestHash = null,
        ?array $diff = null,
        ?array $response = null,
    ): McpWriteOperation {
        return McpWriteOperation::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
            'tool' => $tool,
            'resource' => $resource,
            'operation' => $operation,
            'resource_id' => $resourceId,
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'status' => $status,
            'diff' => $diff === [] ? null : $diff,
            'response' => $response,
            'correlation_id' => $correlationId,
            'recorded_at' => now(),
        ]);
    }
}
