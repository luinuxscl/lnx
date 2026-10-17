<?php

namespace App\Enums;

use App\Models\User;

/**
 * Audience level required to use a knowledge base or an assistant preset.
 *
 * The levels are ordered user < admin < root: a base declares the minimum
 * level, and a user whose level is equal or higher can use it. `root` is the
 * superuser and sees every base (it also bypasses policies via Gate::before).
 */
enum KnowledgeAudience: string
{
    case Root = 'root';

    case Admin = 'admin';

    case User = 'user';

    /**
     * Numeric level used to compare audiences.
     */
    public function level(): int
    {
        return match ($this) {
            self::Root => 3,
            self::Admin => 2,
            self::User => 1,
        };
    }

    /**
     * Resolve the audience of a user from their roles.
     */
    public static function forUser(User $user): self
    {
        return match (true) {
            $user->hasRole(Role::Root) => self::Root,
            $user->hasRole(Role::Admin) => self::Admin,
            default => self::User,
        };
    }

    /**
     * Whether the given user meets this audience level.
     */
    public function allows(User $user): bool
    {
        return self::forUser($user)->level() >= $this->level();
    }

    /**
     * Human-readable label, translated at render time.
     */
    public function label(): string
    {
        return match ($this) {
            self::Root => __('Root only'),
            self::Admin => __('Administrators'),
            self::User => __('All users'),
        };
    }

    /**
     * Audiences a manager may assign when creating a base or preset.
     *
     * Root can declare any audience; admin cannot declare a root-only
     * resource. Root always bypasses the check via Gate::before.
     *
     * @return array<int, self>
     */
    public static function assignableBy(User $user): array
    {
        if ($user->hasRole(Role::Root)) {
            return self::cases();
        }

        return array_values(array_filter(
            self::cases(),
            fn (self $audience): bool => $audience !== self::Root,
        ));
    }
}
