<?php

namespace App\Mcp;

use App\Models\AdministrativeActivity;
use App\Models\Setting;
use App\Models\User;

class Capabilities
{
    /**
     * The read capabilities exposed by the server.
     *
     * @return array<int, array{name: string, description: string, requirements: string}>
     */
    public static function all(): array
    {
        return [
            [
                'name' => 'list-capabilities',
                'description' => 'Discover the read capabilities available to the connected identity.',
                'requirements' => 'any authenticated identity',
            ],
            [
                'name' => 'get-public-configuration',
                'description' => 'Read the non-sensitive public application configuration.',
                'requirements' => 'root',
            ],
            [
                'name' => 'search-users',
                'description' => 'Search and list user accounts with filters and pagination.',
                'requirements' => 'root or admin',
            ],
            [
                'name' => 'get-administrative-activity',
                'description' => 'Read the administrative activity history.',
                'requirements' => 'root',
            ],
        ];
    }

    /**
     * The capabilities the given user is allowed to invoke.
     *
     * @return array<int, array{name: string, description: string, requirements: string}>
     */
    public static function availableTo(User $user): array
    {
        return array_values(array_filter(
            self::all(),
            fn (array $capability): bool => self::allows($user, $capability['name']),
        ));
    }

    private static function allows(User $user, string $name): bool
    {
        return match ($name) {
            'get-public-configuration' => $user->can('viewAny', Setting::class),
            'search-users' => $user->can('viewAny', User::class),
            'get-administrative-activity' => $user->can('viewAny', AdministrativeActivity::class),
            default => true,
        };
    }
}
