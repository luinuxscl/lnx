<?php

namespace App\Models;

use Database\Factories\McpToolInvocationFactory;
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
 * @property string $correlation_id
 * @property string $status
 * @property int|null $result_count
 * @property array<string, mixed>|null $context
 * @property Carbon $recorded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['actor_id', 'actor_name', 'actor_email', 'tool', 'correlation_id', 'status', 'result_count', 'context', 'recorded_at'])]
class McpToolInvocation extends Model
{
    /** @use HasFactory<McpToolInvocationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'recorded_at' => 'datetime',
        ];
    }
}
