<?php

namespace App\Models;

use Database\Factories\AiUsageEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $correlation_id
 * @property int $attempt
 * @property int|null $user_id
 * @property string|null $user_name
 * @property string|null $user_email
 * @property string $application
 * @property string $provider
 * @property string|null $requested_model
 * @property string|null $effective_model
 * @property int|null $input_tokens
 * @property int|null $output_tokens
 * @property int|null $total_tokens
 * @property string|null $reported_cost
 * @property string|null $estimated_cost
 * @property string|null $cost_currency
 * @property string $cost_source
 * @property string|null $pricing_version
 * @property int|null $duration_ms
 * @property string $status
 * @property string|null $error_category
 * @property Carbon $recorded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'correlation_id',
    'attempt',
    'user_id',
    'user_name',
    'user_email',
    'application',
    'provider',
    'requested_model',
    'effective_model',
    'input_tokens',
    'output_tokens',
    'total_tokens',
    'reported_cost',
    'estimated_cost',
    'cost_currency',
    'cost_source',
    'pricing_version',
    'duration_ms',
    'status',
    'error_category',
    'recorded_at',
])]
class AiUsageEvent extends Model
{
    /** @use HasFactory<AiUsageEventFactory> */
    use HasFactory;

    public const STATUS_SUCCESS = 'success';

    public const STATUS_ERROR = 'error';

    public const STATUS_BLOCKED = 'blocked';

    public const COST_REPORTED = 'reported';

    public const COST_ESTIMATED = 'estimated';

    public const COST_UNKNOWN = 'unknown';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'total_tokens' => 'integer',
            'duration_ms' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * The actor this event belongs to, when one is known.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
