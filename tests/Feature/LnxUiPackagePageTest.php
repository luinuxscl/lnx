<?php

use App\Enums\Role;
use App\Livewire\Packages\LnxUi;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('requires authentication for the lnx-ui package page', function () {
    $this->get(route('admin.packages.lnx-ui'))->assertRedirect(route('login'));
});

it('forbids the lnx-ui package page to a regular user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.packages.lnx-ui'))->assertForbidden();
});

it('shows the installed package version and the design gallery for a root user', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Root->value);

    $this->actingAs($user)->get(route('admin.packages.lnx-ui'))
        ->assertOk()
        ->assertSee('lnx-ui')
        ->assertSee('Packages')
        ->assertSee('Componentes UI/UX')
        ->assertSee('Versión instalada')
        ->assertSee('Sin comprobar')
        ->assertSee('Galería de diseño')
        ->assertSee('x-lnx-ui::trend-card')
        ->assertSee('Productos vendidos');

    $this->withSession(['locale' => 'en'])->actingAs($user)->get(route('admin.packages.lnx-ui'))
        ->assertSee('UI/UX components')
        ->assertSee('Installed version')
        ->assertSee('Not checked')
        ->assertSee('Design gallery')
        ->assertSee('Product sold');
});

it('reports an available update from the GitHub API', function () {
    Cache::flush();
    config(['packages.token' => 'test-token']);
    Http::fake(['api.github.com/*' => Http::response([['name' => 'v9.9.9']], 200)]);

    $user = User::factory()->create();
    $user->assignRole(Role::Root->value);

    Livewire::actingAs($user)
        ->test(LnxUi::class)
        ->call('checkForUpdates')
        ->assertSee('9.9.9')
        ->assertSee('Actualización disponible');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.github.com/repos/luinuxscl/lnx-ui/tags'));
});

it('warns when the update check lacks a GitHub token', function () {
    Cache::flush();
    config(['packages.token' => null]);
    Http::fake();

    $user = User::factory()->create();
    $user->assignRole(Role::Root->value);

    Livewire::actingAs($user)
        ->test(LnxUi::class)
        ->call('checkForUpdates')
        ->assertSee('Configura un token de GitHub');

    Http::assertNothingSent();
});
