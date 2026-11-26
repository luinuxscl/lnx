<?php

use App\Enums\Role;
use App\Models\User;
use App\Support\Dashboard\DashboardHome;
use App\Support\Dashboard\DashboardHomeRegistry;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Component;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * Enough users for the Stats widgets to resolve instead of hiding empty.
 */
function dashboardHomeRenderingUsers(): void
{
    User::factory()->count(3)->create();
}

class DashboardHomeSurfaceStub extends Component
{
    public function render()
    {
        return <<<'BLADE'
            <div data-dashboard-home-marker>superficie-propia-de-prueba</div>
            BLADE;
    }
}

class DashboardHomeVisibleStub implements DashboardHome
{
    public function source(): string
    {
        return 'test';
    }

    public function priority(): int
    {
        return 10;
    }

    public function visible(User $actor): bool
    {
        return true;
    }

    public function livewireComponent(): string
    {
        return 'test-home-surface';
    }
}

it('replaces the generic dashboard with the package home while keeping the access section', function () {
    Livewire::component('test-home-surface', DashboardHomeSurfaceStub::class);
    app(DashboardHomeRegistry::class)->register('test', DashboardHomeVisibleStub::class);

    dashboardHomeRenderingUsers();

    $actor = User::factory()->create();
    $actor->assignRole(Role::Admin->value);

    $this->actingAs($actor)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('superficie-propia-de-prueba')
        ->assertDontSee('Bienvenido')
        ->assertSee('Accesos')
        ->assertDontSee('Usuarios totales');
});

class DashboardHomeRenderingHiddenStub implements DashboardHome
{
    public function source(): string
    {
        return 'hidden';
    }

    public function priority(): int
    {
        return 10;
    }

    public function visible(User $actor): bool
    {
        return false;
    }

    public function livewireComponent(): string
    {
        return 'hidden-home';
    }
}

it('keeps the generic dashboard when no home is visible', function () {
    app(DashboardHomeRegistry::class)->register('hidden', DashboardHomeRenderingHiddenStub::class);

    dashboardHomeRenderingUsers();

    $actor = User::factory()->create();
    $actor->assignRole(Role::Admin->value);

    $this->actingAs($actor)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Bienvenido')
        ->assertSee('Usuarios totales');
});
