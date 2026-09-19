<?php

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('visitors see the maintenance page when maintenance mode is enabled', function () {
    Setting::factory()->create([
        'maintenance_mode' => true,
        'maintenance_message' => 'Down for maintenance.',
    ]);

    $this->get(route('home'))
        ->assertStatus(503)
        ->assertSee('Down for maintenance.');
});

test('the maintenance page falls back to a default message', function () {
    Setting::factory()->create([
        'maintenance_mode' => true,
        'maintenance_message' => null,
    ]);

    $this->get(route('home'))
        ->assertStatus(503)
        ->assertSee('mantenimiento programado');
});

test('root users can still browse while maintenance mode is enabled', function () {
    Setting::factory()->create(['maintenance_mode' => true]);

    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    $this->actingAs($root)->get(route('dashboard'))->assertOk();
});

test('the login page stays reachable while maintenance mode is enabled', function () {
    Setting::factory()->create(['maintenance_mode' => true]);

    $this->get(route('login'))->assertOk();
});

test('visitors are not blocked when maintenance mode is disabled', function () {
    Setting::factory()->create(['maintenance_mode' => false]);

    $this->get(route('home'))->assertOk();
});
