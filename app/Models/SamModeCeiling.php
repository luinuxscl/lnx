<?php

namespace App\Models;

use App\Ai\Sam\SamMode;
use Database\Factories\SamModeCeilingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Ceiling of the agent mode for one role.
 *
 * The deployment ceiling in `lnx_sam.max_mode` always wins; this row can only
 * lower it. A role without a row inherits the deployment ceiling, so existing
 * installations keep working without seeding rows.
 *
 * @property int $id
 * @property string $role
 * @property SamMode $mode
 */
class SamModeCeiling extends Model
{
    /** @use HasFactory<SamModeCeilingFactory> */
    use HasFactory;

    protected $fillable = [
        'role',
        'mode',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => SamMode::class,
        ];
    }
}
