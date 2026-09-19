<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Detects whether an application still uses the well-known development
 * password for its root account, so it never silently reaches production.
 */
class DevRootCredentials
{
    /**
     * The insecure password shipped as a development default.
     */
    public const PASSWORD = 'password';

    /**
     * Determine whether any root account still uses the development password.
     */
    public static function isActive(): bool
    {
        return User::role(Role::Root->value)
            ->get()
            ->contains(fn (User $user): bool => Hash::check(self::PASSWORD, $user->password));
    }

    /**
     * Determine whether the given user is a root account using the development password.
     */
    public static function isActiveFor(User $user): bool
    {
        return $user->hasRole(Role::Root)
            && Hash::check(self::PASSWORD, $user->password);
    }
}
