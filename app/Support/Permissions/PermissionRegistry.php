<?php

namespace App\Support\Permissions;

use InvalidArgumentException;

/**
 * In-memory registry of the permissions declared by installed packages.
 *
 * A package's service provider registers its own permissions during boot; the
 * kit never grants anything just because a package sits in `vendor/`. The
 * registry is materialized into the database by the `SyncPermissions` action,
 * invoked from the `RolePermissionSeeder` on a fresh install and from the
 * `permissions:sync` command at runtime.
 */
class PermissionRegistry
{
    /**
     * Registered permission names, keyed by the package source that declared them.
     *
     * @var array<string, list<string>>
     */
    private array $permissions = [];

    /**
     * Register the permissions declared by a package.
     *
     * Names must be prefixed with the package source (e.g. `luz.view_patient`)
     * so ownership stays explicit and a typo or collision is detectable.
     *
     * @param  array<int, string>  $permissions
     *
     * @throws InvalidArgumentException when a name is empty or not namespaced
     */
    public function register(string $source, array $permissions): void
    {
        if ($source === '') {
            throw new InvalidArgumentException('The package source must be a non-empty string.');
        }

        foreach ($permissions as $permission) {
            if ($permission === '') {
                throw new InvalidArgumentException('Permission names must be non-empty strings.');
            }

            if (! str_starts_with($permission, $source.'.')) {
                throw new InvalidArgumentException(
                    "Permission [{$permission}] must be prefixed with the package source [{$source}.]."
                );
            }

            $this->permissions[$source] ??= [];

            if (! in_array($permission, $this->permissions[$source], true)) {
                $this->permissions[$source][] = $permission;
            }
        }
    }

    /**
     * Every registered permission name, deduplicated and in registration order.
     *
     * @return list<string>
     */
    public function all(): array
    {
        $names = [];

        foreach ($this->permissions as $permissions) {
            foreach ($permissions as $permission) {
                if (! in_array($permission, $names, true)) {
                    $names[] = $permission;
                }
            }
        }

        return $names;
    }
}
