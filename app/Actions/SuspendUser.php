<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SuspendUser
{
    public function handle(User $actor, User $target): void
    {
        DB::transaction(function () use ($actor, $target): void {
            $current = User::query()->whereKey($target->id)->firstOrFail();

            abort_if($actor->is($current) || $current->hasRole(Role::Root), 403);
            Gate::forUser($actor)->authorize('suspend', $current);

            User::query()->whereKey($current->getKey())->whereNull('suspended_at')->update([
                'suspended_at' => now(),
                'auth_version' => DB::raw('auth_version + 1'),
                'remember_token' => Str::random(60),
            ]);
        });
    }
}
