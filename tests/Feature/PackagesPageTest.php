<?php

use App\Enums\Role;
use App\Livewire\Packages\Index;
use App\Models\User;
use App\Support\Packages\PackageRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function registerDemoPackage(): void
{
    app(PackageRegistry::class)->register('demo', [
        'name' => 'acme/demo',
        'label' => 'Acme Demo',
        'description' => 'A demo module.',
        'url' => 'https://example.com/demo',
    ]);
}

function registerInstalledPackage(): void
{
    app(PackageRegistry::class)->register('livewire', [
        'name' => 'livewire/livewire',
        'label' => 'Livewire',
    ]);
}

function packagesRoot(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Root->value);

    return $user;
}

it('requires authentication for the packages page', function () {
    $this->get(route('admin.packages.index'))->assertRedirect(route('login'));
});

it('forbids the packages page to a regular user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.packages.index'))->assertForbidden();
});

it('lists a registered package with its installed version', function () {
    registerDemoPackage();

    $this->actingAs(packagesRoot())->get(route('admin.packages.index'))
        ->assertOk()
        ->assertSee('Acme Demo')
        ->assertSee('acme/demo')
        ->assertSee('Versión instalada')
        ->assertSee('Desconocido')
        ->assertSee('Sin comprobar');
});

it('shows an empty state when no package is registered', function () {
    $this->actingAs(packagesRoot())->get(route('admin.packages.index'))
        ->assertOk()
        ->assertSee('Aún no hay paquetes registrados.');
});

it('reports an available update and the update command from the GitHub API', function () {
    Cache::flush();
    config(['packages.token' => 'test-token']);
    Http::fake(['api.github.com/*' => Http::response([['name' => 'v9.9.9']], 200)]);

    registerInstalledPackage();

    Livewire::actingAs(packagesRoot())
        ->test(Index::class)
        ->call('checkForUpdates')
        ->assertSee('9.9.9')
        ->assertSee('Actualización disponible')
        ->assertSee('composer update livewire/livewire');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.github.com/repos/livewire/livewire/tags'));
});

it('warns when the update check lacks a GitHub token', function () {
    Cache::flush();
    config(['packages.token' => null]);
    Http::fake();

    registerDemoPackage();

    Livewire::actingAs(packagesRoot())
        ->test(Index::class)
        ->call('checkForUpdates')
        ->assertSee('Configura un token de GitHub');

    Http::assertNothingSent();
});
