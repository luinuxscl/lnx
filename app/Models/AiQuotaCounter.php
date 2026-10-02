<?php

namespace App\Models;

use Database\Factories\AiQuotaCounterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $scope_type
 * @property string $scope_id
 * @property string $window
 * @property Carbon $window_start
 * @property int $requests
 * @property int $tokens
 * @property int $reserved_tokens
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['scope_type', 'scope_id', 'window', 'window_start', 'requests', 'tokens', 'reserved_tokens'])]
class AiQuotaCounter extends Model
{
    /** @use HasFactory<AiQuotaCounterFactory> */
    use HasFactory;

    public const SCOPE_USER = 'user';

    public const SCOPE_APPLICATION = 'application';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'window_start' => 'datetime',
            'requests' => 'integer',
            'tokens' => 'integer',
            'reserved_tokens' => 'integer',
        ];
    }
}
