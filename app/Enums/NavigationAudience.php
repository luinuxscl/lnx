<?php

namespace App\Enums;

use App\Models\User;

enum NavigationAudience: string
{
    case Root = 'root';

    case Admin = 'admin';

    case User = 'user';

    /**
     * Resolve the audience for the given user.
     *
     * A user with multiple roles sees the menu of the highest-ranking
     * audience: root > admin > user (no inheritance).
     */
    public static function forUser(User $user): self
    {
        return match (true) {
            $user->hasRole(Role::Root) => self::Root,
            $user->hasRole(Role::Admin) => self::Admin,
            default => self::User,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Root => 'Root',
            self::Admin => 'Admin',
            self::User => 'User',
        };
    }
}
