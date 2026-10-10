<?php

use App\Enums\Role;
use App\Models\User;
use App\Support\Permissions\PermissionRegistry;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;

it('materializes the permissions declared by a package and grants them to admin', function () {
    app(PermissionRegistry::class)->register('demo', ['demo.view', 'demo.manage']);

    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    expect(Permission::whereIn('name', ['demo.view', 'demo.manage'])->count())->toBe(2)
        ->and($admin->hasPermissionTo('demo.view'))->toBeTrue()
        ->and($admin->hasPermissionTo('demo.manage'))->toBeTrue()
        ->and($admin->can('demo.view'))->toBeTrue();
});

it('denies a user who was not granted the package permission', function () {
    app(PermissionRegistry::class)->register('demo', ['demo.view', 'demo.manage']);

    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();

    expect($user->can('demo.view'))->toBeFalse()
        ->and($user->can('demo.manage'))->toBeFalse();
});

it('only allows the permissions explicitly granted to a user', function () {
    app(PermissionRegistry::class)->register('demo', ['demo.view', 'demo.manage']);

    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->givePermissionTo('demo.view');

    expect($user->can('demo.view'))->toBeTrue()
        ->and($user->can('demo.manage'))->toBeFalse();
});

it('does not create a permission twice when seeding repeatedly', function () {
    app(PermissionRegistry::class)->register('demo', ['demo.view', 'demo.manage']);

    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    expect(Permission::whereIn('name', ['demo.view', 'demo.manage'])->count())->toBe(2);
});

it('reconciles package permissions through the permissions:sync command', function () {
    $this->seed(RolePermissionSeeder::class);

    app(PermissionRegistry::class)->register('demo', ['demo.view']);

    $this->artisan('permissions:sync')
        ->expectsOutput('Permissions: 1 created, 1 granted to admin.')
        ->assertSuccessful();

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    expect($admin->can('demo.view'))->toBeTrue();
});

it('rejects a permission that is not prefixed with the package source', function () {
    app(PermissionRegistry::class)->register('demo', ['view']);
})->throws(InvalidArgumentException::class);
