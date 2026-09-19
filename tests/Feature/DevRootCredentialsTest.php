<?php

use App\Enums\Role;
use App\Models\User;
use App\Support\DevRootCredentials;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    config()->set('authorization.root_user.name', 'Luis Sepulveda');
    config()->set('authorization.root_user.email', 'luinuxscl@gmail.com');
    config()->set('authorization.root_user.password', DevRootCredentials::PASSWORD);
});

it('seeds the development root user in non-production environments', function () {
    $this->seed(RolePermissionSeeder::class);

    $root = User::where('email', 'luinuxscl@gmail.com')->first();

    expect($root)->not->toBeNull()
        ->and($root->name)->toBe('Luis Sepulveda')
        ->and($root->hasRole(Role::Root))->toBeTrue();
});

it('refuses to seed the development password in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    expect(fn () => app(RolePermissionSeeder::class)->run())
        ->toThrow(RuntimeException::class);

    expect(User::where('email', 'luinuxscl@gmail.com')->exists())->toBeFalse();
});

it('detects the development password while it is still in use', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(DevRootCredentials::isActive())->toBeTrue();

    $root = User::where('email', 'luinuxscl@gmail.com')->firstOrFail();
    $root->update(['password' => 'a-strong-new-password']);

    expect(DevRootCredentials::isActive())->toBeFalse();
});

it('does not flag non-root users even with the development password', function () {
    $user = User::factory()->create(['password' => DevRootCredentials::PASSWORD]);

    expect(DevRootCredentials::isActiveFor($user))->toBeFalse();
});

it('shows the warning banner to the root user with the development password', function () {
    $this->seed(RolePermissionSeeder::class);

    $root = User::where('email', 'luinuxscl@gmail.com')->firstOrFail();

    $this->actingAs($root)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee(__('Development credentials in use'));
});

it('hides the warning banner once the root password is changed', function () {
    $this->seed(RolePermissionSeeder::class);

    $root = User::where('email', 'luinuxscl@gmail.com')->firstOrFail();
    $root->update(['password' => 'a-strong-new-password']);

    $this->actingAs($root->fresh())
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee(__('Development credentials in use'));
});

it('hides the warning banner from non-root users', function () {
    $user = User::factory()->create(['password' => DevRootCredentials::PASSWORD]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee(__('Development credentials in use'));
});
