<?php

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('renders the configured text once inside the content footer', function () {
    Setting::factory()->create(['footer_text' => 'A unique footer message']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSeeInOrder(['<footer', 'A unique footer message', '</footer>'], false);
    expect(substr_count($response->getContent(), 'A unique footer message'))->toBe(1);
});

it('renders the brand without a version for a role-less user in production', function () {
    config()->set('app.version', 'v0.1.0');
    app()->detectEnvironment(fn () => 'production');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Codeado con')
        ->assertSee('Like Innovación')
        ->assertSee('https://like.cl')
        ->assertDontSee('v0.1.0');
});

it('renders the version to any user outside production', function () {
    config()->set('app.version', 'v9.8.7');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('LNX v9.8.7');
});

it('renders the configured version for elevated roles', function (Role $role) {
    config()->set('app.version', 'v9.8.7');
    $user = User::factory()->create();
    $user->assignRole($role->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('LNX v9.8.7');
})->with([
    'admin' => Role::Admin,
    'root' => Role::Root,
]);

it('renders the configured app name in the version footer', function () {
    config()->set('app.name', 'MiApp');
    config()->set('app.version', 'v9.8.7');
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('MiApp v9.8.7')
        ->assertDontSee('LNX v');
});

it('translates the footer into English', function () {
    config()->set('app.version', 'v0.1.0');
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $this->actingAs($user)
        ->withSession(['locale' => 'en'])
        ->get(route('dashboard'))
        ->assertSee('Coded with')
        ->assertSee('LNX v0.1.0');
});
