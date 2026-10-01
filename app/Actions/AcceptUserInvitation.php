<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptUserInvitation
{
    public function handle(UserInvitation $invitation, string $token, string $name, string $password): User
    {
        return DB::transaction(function () use ($invitation, $token, $name, $password): User {
            $current = UserInvitation::query()->findOrFail($invitation->id);

            if (! $current->isPending() || ! hash_equals($current->token_hash, hash('sha256', $token))) {
                throw ValidationException::withMessages(['invitation' => __('This invitation is no longer available.')]);
            }

            if ($current->role !== null && ! in_array($current->role, Role::assignableValues(), true)) {
                throw ValidationException::withMessages(['invitation' => __('This invitation is no longer available.')]);
            }

            if (User::query()->whereRaw('lower(email) = ?', [$current->email])->exists()) {
                throw ValidationException::withMessages(['invitation' => __('This email already belongs to a user.')]);
            }

            $claimed = UserInvitation::query()->whereKey($current->id)
                ->where('token_hash', hash('sha256', $token))
                ->whereNull('accepted_at')->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->update(['accepted_at' => now(), 'pending_email' => null]);

            if ($claimed !== 1) {
                throw ValidationException::withMessages(['invitation' => __('This invitation is no longer available.')]);
            }

            $user = User::create(['name' => $name, 'email' => $current->email, 'password' => $password]);
            $user->forceFill(['email_verified_at' => now()])->save();

            if ($current->role !== null) {
                $user->assignRole($current->role);
            }

            return $user;
        });
    }
}
