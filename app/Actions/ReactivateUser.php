<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReactivateUser
{
    public function handle(User $actor, User $target): void
    {
        DB::transaction(function () use ($actor, $target): void {
            $current = User::query()->whereKey($target->id)->firstOrFail();

            abort_if($actor->is($current) || $current->hasRole(Role::Root), 403);
            Gate::forUser($actor)->authorize('reactivate', $current);

            User::query()->whereKey($current->getKey())->whereNotNull('suspended_at')->update([
                'suspended_at' => null,
            ]);
        });
    }
}
