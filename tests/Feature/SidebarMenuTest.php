<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\NavigationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(NavigationSeeder::class);
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

it('shows the administration group but not the repository to admin users', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Administración')
        ->assertSee('Usuarios')
        ->assertDontSee('Documentación')
        ->assertDontSee('Repositorio')
        ->assertDontSee('Configuración')
        ->assertDontSee('Developer Mode');
});

it('prioritizes root tools inside developer mode and hides repository and documentation from root users', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Root->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Developer Mode')
        ->assertSee('Soporte')
        ->assertSee(route('admin.settings.edit'))
        ->assertSee(route('admin.setup.index'))
        ->assertSee(route('admin.ai.index'))
        ->assertSeeInOrder(['Dashboard', 'Developer Mode'])
        ->assertDontSee('Repositorio')
        ->assertDontSee('https://github.com/luinuxscl/lnx')
        ->assertDontSee('Documentación');
});

it('keeps the kit ai tools out of the standard user sidebar', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertDontSee('AI Tools')
        ->assertDontSee('Chat')
        ->assertDontSee('RAG')
        ->assertDontSee('Document AI')
        ->assertDontSee('Administración')
        ->assertDontSee('Developer Mode');
});

it('shows the curated administration group to admin and developer mode to root', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Administración')
        ->assertSee('Usuarios')
        ->assertDontSee('Developer Mode')
        ->assertDontSee('Chat')
        ->assertDontSee(route('admin.settings.edit'));

    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    $this->actingAs($root)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Developer Mode')
        ->assertSee('Soporte')
        ->assertSee('Chat')
        ->assertSee(route('admin.settings.edit'));
});

it('renders the managed sidebar without leaking template directives', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Root->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('@if')
        ->assertDontSee('@endif');
});

it('opens only the group that contains the current page', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Root->value);

    $users = $this->actingAs($user)->get(route('admin.users.index'))->getContent();
    expect(preg_match_all('/open\s+data-flux-sidebar-group/', $users))->toBe(1);

    $dashboard = $this->actingAs($user)->get(route('dashboard'))->getContent();
    expect(preg_match_all('/open\s+data-flux-sidebar-group/', $dashboard))->toBe(0);
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
