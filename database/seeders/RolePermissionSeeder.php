<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Support\DevRootCredentials;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the default application roles and the initial root user.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RoleEnum::cases() as $role) {
            Role::findOrCreate($role->value);
        }

        $this->seedRootUser();
    }

    /**
     * Create the initial superuser account when credentials are configured.
     */
    protected function seedRootUser(): void
    {
        $email = config('authorization.root_user.email');
        $password = config('authorization.root_user.password');

        if (blank($email) || blank($password)) {
            return;
        }

        if ($this->isUsingDevPasswordInProduction($password)) {
            throw new RuntimeException(
                'Refusing to seed the root user with the development password in production. '
                .'Set a strong ROOT_USER_PASSWORD before deploying.'
            );
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => config('authorization.root_user.name'),
                'password' => $password,
            ],
        );

        $user->assignRole(RoleEnum::Root->value);

        if ($password === DevRootCredentials::PASSWORD) {
            $this->warnAboutDevPassword();
        }
    }

    /**
     * Determine whether the development password would reach production.
     */
    protected function isUsingDevPasswordInProduction(string $password): bool
    {
        return app()->isProduction() && $password === DevRootCredentials::PASSWORD;
    }

    /**
     * Warn about the insecure development password.
     */
    protected function warnAboutDevPassword(): void
    {
        $message = 'The root user was seeded with the default development password. Change it before deploying.';

        Log::warning($message);

        if ($this->command !== null) {
            $this->command->warn($message);
        }
    }
}
