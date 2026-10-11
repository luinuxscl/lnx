<?php

namespace Tests\Support;

use App\Enums\Role;
use App\Models\User;
use App\Support\RecordAccess\AccessControlled;
use App\Support\RecordAccess\GrantsRecordAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RecordAccessTestPatient extends Model implements AccessControlled
{
    use GrantsRecordAccess;

    protected $table = 'record_access_test_patients';

    protected $guarded = [];

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole(Role::Root)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('owner_id', $user->id)
                ->orWhereHas('accessGrants', function ($grant) use ($user): void {
                    $grant->where('user_id', $user->id)
                        ->where(fn ($expiry) => $expiry
                            ->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now()));
                });
        });
    }
}
