<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;

/**
 * Builds the deterministic development accounts and bulk sample users used by
 * the `lnx:reset` command and the demo seeder.
 *
 * Every account shares the same development password and the bulk sample users
 * keep a fixed verified/unverified split, so seeded environments are easy to
 * reason about.
 */
class DemoUsers
{
    /**
     * The shared password for every demo account.
     */
    public const PASSWORD = 'password';

    /**
     * Bulk sample users created when no explicit count is given.
     */
    public const DEFAULT_FAKER_COUNT = 100;

    /**
     * Fraction of bulk sample users left unverified.
     */
    public const UNVERIFIED_RATIO = 0.1;

    /**
     * The fixed verified administrator account.
     */
    public const ADMIN_NAME = 'Samantha';

    public const ADMIN_EMAIL = 'admin@lnx.dev';

    /**
     * The fixed verified regular account.
     */
    public const USER_NAME = 'Leonora';

    public const USER_EMAIL = 'user@lnx.dev';

    /**
     * Create (or update) the fixed demo accounts with their roles.
     *
     * @return array{admin: User, user: User}
     */
    public function seedFixedAccounts(): array
    {
        $admin = User::query()->updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            ['name' => self::ADMIN_NAME, 'password' => self::PASSWORD],
        );
        $admin->forceFill(['email_verified_at' => now()])->save();
        $admin->syncRoles([Role::Admin->value]);

        $user = User::query()->updateOrCreate(
            ['email' => self::USER_EMAIL],
            ['name' => self::USER_NAME, 'password' => self::PASSWORD],
        );
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->syncRoles([]);

        return ['admin' => $admin, 'user' => $user];
    }

    /**
     * Create the bulk sample users with an exact verified/unverified split.
     *
     * @return array{verified: int, unverified: int}
     */
    public function seedFakerUsers(int $count, ?int $seed = null): array
    {
        $count = max(0, $count);

        if ($count === 0) {
            return ['verified' => 0, 'unverified' => 0];
        }

        if ($seed !== null) {
            fake()->seed($seed);
        }

        $unverified = (int) floor($count * self::UNVERIFIED_RATIO);
        $verified = $count - $unverified;

        if ($verified > 0) {
            User::factory()->count($verified)->create();
        }

        if ($unverified > 0) {
            User::factory()->count($unverified)->unverified()->create();
        }

        return ['verified' => $verified, 'unverified' => $unverified];
    }
}
