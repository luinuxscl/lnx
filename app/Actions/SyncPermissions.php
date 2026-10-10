<?php

namespace App\Actions;

use App\Enums\Role as RoleEnum;
use App\Support\Permissions\PermissionRegistry;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Materializes the permissions declared by packages into the database.
 *
 * Each registered permission is created idempotently with `findOrCreate` and
 * granted to the `admin` role by default (root bypasses every gate). Running it
 * twice is a no-op.
 */
class SyncPermissions
{
    public function __construct(private readonly PermissionRegistry $registry) {}

    /**
     * @return array{created: int, granted: int}
     */
    public function handle(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = $this->registry->all();

        if ($names === []) {
            return ['created' => 0, 'granted' => 0];
        }

        $existing = Permission::query()->whereIn('name', $names)->pluck('name')->all();

        $created = 0;

        foreach ($names as $name) {
            if (! in_array($name, $existing, true)) {
                Permission::findOrCreate($name);
                $created++;
            }
        }

        $admin = Role::findByName(RoleEnum::Admin->value);
        $alreadyGranted = $admin->permissions()->pluck('name')->all();

        $admin->givePermissionTo($names);

        $granted = count(array_diff($names, $alreadyGranted));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return ['created' => $created, 'granted' => $granted];
    }
}
