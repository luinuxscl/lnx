<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('hides the configuration menu from users without an admin role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Repositorio')
        ->assertDontSee('Configuración general')
        ->assertDontSee('Documentación');
});

it('shows documentation but not the repository to admin users', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Documentación')
        ->assertDontSee('Repositorio')
        ->assertDontSee('Configuración general');
});

it('shows the repository of this application to root users', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Root->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Repositorio')
        ->assertSee('https://github.com/luinuxscl/lnx')
        ->assertSee('Configuración general')
        ->assertSee('admin/settings')
        ->assertSee('Documentación')
        ->assertDontSee('laravel.com/docs');
});
