<?php

namespace App\Support\RecordAccess;

use App\Models\RecordAccess;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Optional convenience for a record that grants access to arbitrary users.
 *
 * Applied to an access-controlled model, it exposes the polymorphic
 * `record_access` relation used to relate users with the record, plus a pair of
 * helpers to create a grant and to check one. A package that needs something
 * richer than this can define its own relation and still satisfy
 * {@see AccessControlled}.
 */
trait GrantsRecordAccess
{
    /**
     * The grants issued over this record.
     *
     * @return MorphMany<RecordAccess, $this>
     */
    public function accessGrants(): MorphMany
    {
        return $this->morphMany(RecordAccess::class, 'record');
    }

    /**
     * Grant a user an ability over this record, optionally expiring.
     *
     * Abilities are merged, so granting `view` then `manage` keeps both.
     */
    public function grantAccessTo(
        User $user,
        RecordAccessAbility $ability = RecordAccessAbility::View,
        ?CarbonInterface $expiresAt = null,
    ): RecordAccess {
        $grant = $this->accessGrants()->firstOrNew(['user_id' => $user->id]);

        $abilities = $grant->abilities ?? [];

        if (! in_array($ability->value, $abilities, true)) {
            $abilities[] = $ability->value;
        }

        $grant->abilities = $abilities;

        if ($expiresAt !== null) {
            $grant->expires_at = $expiresAt;
        }

        $grant->save();

        return $grant;
    }

    /**
     * Whether the user currently holds the given ability over this record.
     */
    public function hasRecordAccess(User $user, RecordAccessAbility $ability = RecordAccessAbility::View): bool
    {
        return $this->accessGrants()
            ->where('user_id', $user->id)
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->get()
            ->contains(fn (RecordAccess $grant): bool => $grant->allows($ability));
    }
}
