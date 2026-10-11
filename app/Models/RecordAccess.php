<?php

namespace App\Models;

use App\Support\RecordAccess\GrantsRecordAccess;
use App\Support\RecordAccess\RecordAccessAbility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * A user's grant over a single record.
 *
 * This is the kit's optional, generic convenience for relating a user with any
 * record: a package may use it through {@see GrantsRecordAccess}
 * or define its own richer relation and still satisfy the contract. The
 * `abilities` list and `expires_at` are optional; a grant with no abilities
 * stored allows every ability.
 *
 * @property int $id
 * @property int $user_id
 * @property string $record_type
 * @property int $record_id
 * @property list<string>|null $abilities
 * @property Carbon|null $expires_at
 */
class RecordAccess extends Model
{
    protected $table = 'record_access';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function record(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Whether the grant is still in force (no expiry or a future one).
     */
    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /**
     * Whether the grant carries the given ability.
     *
     * An empty ability list means the grant covers every ability.
     */
    public function allows(RecordAccessAbility $ability): bool
    {
        $abilities = $this->abilities;

        return $abilities === null || $abilities === [] || in_array($ability->value, $abilities, true);
    }
}
