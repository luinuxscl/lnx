<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role as SpatieRole;

it('seeds the default root and admin roles', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(SpatieRole::pluck('name')->all())
        ->toContain(Role::Root->value, Role::Admin->value);
});

it('seeds the roles only once when run repeatedly', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    expect(SpatieRole::whereIn('name', [Role::Root->value, Role::Admin->value])->count())->toBe(2);
});

it('lets a root user bypass every gate check', function () {
    $this->seed(RolePermissionSeeder::class);

    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    expect(Gate::forUser($root)->allows('any-ability'))->toBeTrue()
        ->and(Gate::forUser($root)->denies('another-ability'))->toBeFalse();
});

it('does not grant elevated access to users without the root role', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();

    expect($user->hasRole(Role::Root))->toBeFalse()
        ->and($user->hasRole(Role::Admin))->toBeFalse()
        ->and(Gate::forUser($user)->allows('any-ability'))->toBeFalse();
});

it('creates the configured root user with the root role', function () {
    config()->set('authorization.root_user.email', 'root@example.com');
    config()->set('authorization.root_user.password', 'secret-password');

    $this->seed(RolePermissionSeeder::class);

    $root = User::where('email', 'root@example.com')->first();

    expect($root)->not->toBeNull()
        ->and($root->hasRole(Role::Root))->toBeTrue();
});
