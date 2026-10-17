<?php

namespace App\Actions;

use App\Enums\AdministrativeAction;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateVerifiedUser
{
    /**
     * Create a user directly, with their email already verified.
     *
     * Reserved for superusers: bypasses the invitation flow, so the actor must
     * be trusted with the email address and the verified status.
     */
    public function handle(User $actor, string $name, string $email, string $password, ?string $role = null): User
    {
        abort_unless($actor->hasRole(Role::Root), 403);

        $email = Str::lower(trim($email));

        if ($role !== null && ! in_array($role, Role::assignableValues(), true)) {
            throw ValidationException::withMessages(['role' => __('Invalid role.')]);
        }

        return DB::transaction(function () use ($actor, $name, $email, $password, $role): User {
            if (User::query()->whereRaw('lower(email) = ?', [$email])->exists()) {
                throw ValidationException::withMessages(['email' => __('This email already belongs to a user.')]);
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            if ($role !== null) {
                $user->assignRole($role);
            }

            app(RecordAdministrativeActivity::class)->handle($actor, AdministrativeAction::UserCreated, $user, [], [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
            ]);

            return $user;
        });
    }
}
