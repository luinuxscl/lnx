<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('hides the configuration menu from users without an admin role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Repositorio')
        ->assertDontSee('Configuración')
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
        ->assertDontSee('Configuración');
});

it('shows the repository of this application to root users', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Root->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Repositorio')
        ->assertSee('https://github.com/luinuxscl/lnx')
        ->assertSee('Configuración')
        ->assertSee('admin/settings')
        ->assertSee('Documentación')
        ->assertDontSee('laravel.com/docs');
});

it('groups AI tools and hides administration group from standard users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('AI Tools')
        ->assertSee('Chat')
        ->assertSee('RAG')
        ->assertSee('Document AI')
        ->assertDontSee('Administración');
});

it('shows administration domain group to admin and root users', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('AI Tools')
        ->assertSee('Administración')
        ->assertSee('Usuarios');

    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    $this->actingAs($root)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('AI Tools')
        ->assertSee('Administración')
        ->assertSee('Configuración');
});

it('shows the uploaded avatar in desktop and mobile menus with Gravatar fallback', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $path = UploadedFile::fake()->image('avatar.png')->store("avatars/{$user->id}", 'public');
    $user->forceFill(['avatar_path' => $path])->save();

    $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect(substr_count($response->getContent(), 'src="'.Storage::disk('public')->url($path).'"'))->toBe(4);
    $response->assertSee(hash('sha256', strtolower($user->email)), escape: false);
    $response->assertSee('x-on:error="fallback()"', escape: false);
});
