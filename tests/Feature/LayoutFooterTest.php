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

it('renders the brand without configured text or an elevated role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Codeado con')
        ->assertSee('Like Innovación')
        ->assertSee('https://like.cl')
        ->assertDontSee('LNX v0.1.0');
});

it('renders the configured version for elevated roles', function (Role $role) {
    config()->set('app.version', '9.8.7');
    $user = User::factory()->create();
    $user->assignRole($role->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('LNX v9.8.7');
})->with([
    'admin' => Role::Admin,
    'root' => Role::Root,
]);

it('translates the footer into English', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $this->actingAs($user)
        ->withSession(['locale' => 'en'])
        ->get(route('dashboard'))
        ->assertSee('Coded with')
        ->assertSee('LNX v0.1.0');
});
