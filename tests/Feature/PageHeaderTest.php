<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('renders the page title as the page heading', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('<h1', false)
        ->assertSee('Usuarios');
});

it('renders the ancestor breadcrumb link on a nested page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->get(route('admin.users.invitations'))
        ->assertOk()
        ->assertSee('Invitaciones')
        ->assertSee(route('admin.users.index'), false);
});

it('renders the settings heading through the shared partial', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('<h1', false)
        ->assertSee('Ajustes');
});
