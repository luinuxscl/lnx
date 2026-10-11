<?php

namespace App\Models;

use Database\Factories\McpWriteOperationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $actor_id
 * @property string|null $actor_name
 * @property string|null $actor_email
 * @property string $tool
 * @property string $resource
 * @property string $operation
 * @property string|null $resource_id
 * @property string|null $idempotency_key
 * @property string|null $request_hash
 * @property string $status
 * @property array<string, array{old: mixed, new: mixed}>|null $diff
 * @property array<string, mixed>|null $response
 * @property string $correlation_id
 * @property Carbon $recorded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['actor_id', 'actor_name', 'actor_email', 'tool', 'resource', 'operation', 'resource_id', 'idempotency_key', 'request_hash', 'status', 'diff', 'response', 'correlation_id', 'recorded_at'])]
class McpWriteOperation extends Model
{
    /** @use HasFactory<McpWriteOperationFactory> */
    use HasFactory;

    public const STATUS_SUCCESS = 'success';

    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_DENIED = 'denied';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_UNAUTHENTICATED = 'unauthenticated';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diff' => 'array',
            'response' => 'array',
            'recorded_at' => 'datetime',
        ];
    }
}
