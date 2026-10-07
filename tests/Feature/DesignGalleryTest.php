<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('requires authentication for the local design gallery', function () {
    $this->get(route('design-gallery'))->assertRedirect(route('login'));
});

it('forbids the local design gallery to a regular user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('design-gallery'))->assertForbidden();
});

it('renders interface states for a root user in both locales', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Root->value);

    $this->actingAs($user)->get(route('design-gallery'))
        ->assertSee('Galería de diseño')
        ->assertSee('Biblioteca compartida (lnx-ui)')
        ->assertSee('x-lnx-ui::trend-card')
        ->assertSee('Productos vendidos')
        ->assertSee('Estado vacío')
        ->assertSee('Estado de error')
        ->assertSee('Validación')
        ->assertSee('Introduce una dirección de correo válida.');

    $this->withSession(['locale' => 'en'])->actingAs($user)->get(route('design-gallery'))
        ->assertSee('Design gallery')
        ->assertSee('Shared library (lnx-ui)')
        ->assertSee('x-lnx-ui::trend-card')
        ->assertSee('Product sold')
        ->assertSee('Empty state')
        ->assertSee('Error state')
        ->assertSee('Validation')
        ->assertSee('Enter a valid email address.');
});
