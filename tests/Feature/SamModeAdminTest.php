<?php

use App\Actions\UpdateSamModeCeiling;
use App\Enums\AdministrativeAction;
use App\Enums\Role;
use App\Livewire\Sam\Index;
use App\Models\AdministrativeActivity;
use App\Models\SamModeCeiling;
use App\Models\User;
use Database\Seeders\NavigationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Luinuxscl\LnxSam\SamMode;
use Luinuxscl\LnxSam\SamModeResolver;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function samCeilingActor(Role $role = Role::Root): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

function samSalesRole(): SpatieRole
{
    return SpatieRole::query()->firstOrCreate([
        'name' => 'sales',
        'guard_name' => 'web',
    ]);
}

it('restricts the ceiling screen to root', function (): void {
    $this->get(route('admin.sam.index'))->assertRedirect(route('login'));

    foreach ([null, Role::Admin] as $role) {
        $user = $role === null ? User::factory()->create() : samCeilingActor($role);
        $this->actingAs($user)->get(route('admin.sam.index'))->assertForbidden();
        Livewire::actingAs($user)->test(Index::class)->assertForbidden();
    }

    $this->seed(NavigationSeeder::class);

    $this->actingAs(samCeilingActor())
        ->get(route('admin.sam.index'))
        ->assertOk()
        ->assertSee(__('The deployment ceiling always wins.'))
        ->assertSee(__('If the deployment ceiling is read only, setting draft on a role does not change the effective mode.'));

    $this->actingAs(samCeilingActor())
        ->get(route('dashboard'))
        ->assertSee(route('admin.sam.index'));
});

it('lists every role from the permission table', function (): void {
    samSalesRole();

    Livewire::actingAs(samCeilingActor())
        ->test(Index::class)
        ->assertSee('sales')
        ->assertSee(__('Administrator'));
});

it('stores a role ceiling without clipping it and audits one change', function (): void {
    config()->set('lnx_sam.max_mode', 'draft');
    $sales = samSalesRole();
    $root = samCeilingActor();

    Livewire::actingAs($root)
        ->test(Index::class)
        ->set('ceilings.'.$sales->id, SamMode::Draft->value)
        ->call('save', $sales->id)
        ->assertHasNoErrors();

    $user = User::factory()->create();
    $user->assignRole('sales');

    $activity = AdministrativeActivity::query()->where('action', AdministrativeAction::SamModeCeilingUpdated)->first();

    expect(SamModeCeiling::query()->where('role', 'sales')->first()?->mode)->toBe(SamMode::Draft)
        ->and(app(SamModeResolver::class)->for($user))->toBe(SamMode::Draft)
        ->and(AdministrativeActivity::query()->where('action', AdministrativeAction::SamModeCeilingUpdated)->count())->toBe(1)
        ->and($activity)->not->toBeNull()
        ->and($activity->resource_name)->toBe('sales')
        ->and($activity->changes['mode'])->toBe(['old' => null, 'new' => 'draft']);

    Livewire::actingAs($root)->test(Index::class)->call('save', $sales->id);

    expect(AdministrativeActivity::query()->where('action', AdministrativeAction::SamModeCeilingUpdated)->count())->toBe(1);
});

it('clears a ceiling when the role inherits and records it as inherit', function (): void {
    config()->set('lnx_sam.max_mode', 'draft');
    $sales = samSalesRole();
    SamModeCeiling::factory()->create(['role' => 'sales', 'mode' => SamMode::ReadOnly]);

    Livewire::actingAs(samCeilingActor())
        ->test(Index::class)
        ->set('ceilings.'.$sales->id, UpdateSamModeCeiling::INHERIT)
        ->call('save', $sales->id)
        ->assertHasNoErrors();

    $user = User::factory()->create();
    $user->assignRole('sales');

    expect(SamModeCeiling::query()->where('role', 'sales')->exists())->toBeFalse()
        ->and(app(SamModeResolver::class)->for($user))->toBe(SamMode::Draft)
        ->and(AdministrativeActivity::query()->where('action', AdministrativeAction::SamModeCeilingUpdated)->count())->toBe(1)
        ->and(AdministrativeActivity::query()->first()->changes['mode']['new'])->toBe(UpdateSamModeCeiling::INHERIT);
});

it('warns before saving a ceiling the deployment ceiling will ignore', function (): void {
    config()->set('lnx_sam.max_mode', 'read_only');
    $sales = samSalesRole();

    Livewire::actingAs(samCeilingActor())
        ->test(Index::class)
        ->set('ceilings.'.$sales->id, SamMode::Draft->value)
        ->assertSee(__('This ceiling is above the deployment ceiling, so it does not change the effective mode.'))
        ->call('save', $sales->id)
        ->assertHasNoErrors();

    $user = User::factory()->create();
    $user->assignRole('sales');

    expect(SamModeCeiling::query()->where('role', 'sales')->first()?->mode)->toBe(SamMode::Draft)
        ->and(app(SamModeResolver::class)->for($user))->toBe(SamMode::ReadOnly);
});

it('rejects a role that is not in the permission table', function (): void {
    Livewire::actingAs(samCeilingActor())
        ->test(Index::class)
        ->call('save', 99999)
        ->assertSee(__('That role does not exist.'));

    expect(AdministrativeActivity::query()->count())->toBe(0)
        ->and(SamModeCeiling::query()->count())->toBe(0);
});
