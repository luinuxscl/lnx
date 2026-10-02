<?php

use App\Actions\DiagnoseSetup;
use App\Enums\Role;
use App\Livewire\Dashboard;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserInvitation;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

class DashboardDiagnoseSetup extends DiagnoseSetup
{
    public static ?bool $writable = null;

    protected function storageLinkExists(): bool
    {
        return true;
    }

    protected function storageIsWritable(): bool
    {
        return static::$writable ?? parent::storageIsWritable();
    }
}

beforeEach(function () {
    DashboardDiagnoseSetup::$writable = null;
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');
});

function dashboardUser(?Role $role = null): User
{
    $user = User::factory()->create();

    if ($role !== null) {
        $user->assignRole($role->value);
    }

    return $user;
}

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('ordinary users see only their personal shortcuts and an escaped name', function () {
    $user = User::factory()->create(['name' => '<script>alert(1)</script>']);
    dashboardUser(Role::Admin);
    UserInvitation::factory()->create(['invited_by' => $user->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee(route('profile.edit'))
        ->assertSee(route('security.edit'))
        ->assertDontSee('Usuarios totales')
        ->assertDontSee('Invitaciones pendientes')
        ->assertDontSee('de 5 verificadas')
        ->assertDontSee(route('admin.users.index'))
        ->assertDontSee(route('admin.setup.index'));

    $component = Livewire::actingAs($user)->test(Dashboard::class);
    expect($component->html())->not->toContain('Usuarios totales', 'Invitaciones pendientes', 'de 5 verificadas');
    expect(json_encode($component->effects))->not->toContain('userCounts', 'pendingInvitations', 'setup');
});

test('admin sees all account counts and only actionable pending invitations', function () {
    $admin = dashboardUser(Role::Admin);
    $active = dashboardUser();
    $suspended = dashboardUser();
    $suspended->forceFill(['suspended_at' => now()])->save();
    $root = dashboardUser(Role::Root);
    UserInvitation::factory()->create(['invited_by' => $admin->id]);
    UserInvitation::factory()->create(['invited_by' => $admin->id, 'send_failed_at' => now()]);
    UserInvitation::factory()->create(['invited_by' => $admin->id, 'accepted_at' => now()]);
    UserInvitation::factory()->create(['invited_by' => $admin->id, 'revoked_at' => now()]);
    UserInvitation::factory()->create(['invited_by' => $admin->id, 'expires_at' => now()->subMinute()]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertSee('Usuarios totales')
        ->assertSee('Usuarios activos')
        ->assertSee('Usuarios suspendidos')
        ->assertSee('Invitaciones pendientes')
        ->assertSee(route('admin.users.index', ['status' => 'active']))
        ->assertSee(route('admin.users.index', ['status' => 'suspended']))
        ->assertSee(route('admin.users.invitations'))
        ->assertSee(route('admin.users.create'))
        ->assertDontSee('de 5 verificadas')
        ->assertDontSee(route('admin.setup.index'))
        ->assertDontSee(route('admin.settings.edit'));

    $component = Livewire::actingAs($admin)->test(Dashboard::class);
    expect((int) $component->viewData('userCounts')->total)->toBe(5)
        ->and((int) $component->viewData('userCounts')->active)->toBe(4)
        ->and((int) $component->viewData('userCounts')->suspended)->toBe(1)
        ->and($component->viewData('pendingInvitations'))->toBe(2);
});

test('admin sees a truthful empty invitation state', function () {
    $admin = dashboardUser(Role::Admin);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertSee('No hay invitaciones pendientes de aceptación.');
});

test('root sees pending setup checks without invented activity', function () {
    $root = dashboardUser(Role::Root);
    $this->app->bind(DiagnoseSetup::class, DashboardDiagnoseSetup::class);

    $this->actingAs($root)->get(route('dashboard'))
        ->assertSee('1 de 5 verificadas')
        ->assertSee('Pendientes: 4')
        ->assertSee('Con errores: 0');
});

test('root sees shared checklist summary and storage probe is cleaned', function () {
    $root = dashboardUser(Role::Root);
    Setting::factory()->create(['site_title' => 'Site', 'admin_email' => 'admin@example.com', 'test_email_sent_at' => now(), 'test_email_sent_to' => 'admin@example.com']);
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.default_model', 'test/model');
    config()->set('lnx_ai.allowed_models', ['test/model' => []]);
    config()->set('ai.providers.openrouter.key', 'test-key');
    $this->app->bind(DiagnoseSetup::class, DashboardDiagnoseSetup::class);

    $response = $this->actingAs($root)->get(route('dashboard'));

    $response->assertSee('5 de 5 verificadas')
        ->assertSee('Todas las comprobaciones están verificadas.')
        ->assertSee(route('admin.setup.index'))
        ->assertSee(route('admin.settings.edit'));
    expect(Storage::disk('public')->allFiles())->toBe([]);

    $this->get(route('admin.setup.index'))->assertSee('Verificado');
});

test('root sees recoverable storage error on dashboard and checklist', function () {
    $root = dashboardUser(Role::Root);
    $this->app->bind(DiagnoseSetup::class, DashboardDiagnoseSetup::class);
    DashboardDiagnoseSetup::$writable = false;

    $this->actingAs($root)->get(route('dashboard'))
        ->assertSee('Con errores')
        ->assertSee(route('admin.setup.index'));
    $this->get(route('admin.setup.index'))->assertSee('Error');
});

test('removing an admin role between Livewire requests hides admin data', function () {
    $admin = dashboardUser(Role::Admin);
    $component = Livewire::actingAs($admin)->test(Dashboard::class)->assertSee('Usuarios totales');

    $admin->removeRole(Role::Admin->value);

    $component->call('$refresh')->assertDontSee('Usuarios totales')->assertDontSee('Invitaciones pendientes');
    expect(json_encode($component->effects))->not->toContain('userCounts', 'pendingInvitations');
});

test('removing root role between Livewire requests hides setup data', function () {
    $root = dashboardUser(Role::Root);
    $component = Livewire::actingAs($root)->test(Dashboard::class)->assertSee('de 5 verificadas');

    $root->removeRole(Role::Root->value);

    $component->call('$refresh')->assertDontSee('de 5 verificadas');
    expect(json_encode($component->effects))->not->toContain('setup');
});

test('storage read failures keep the root dashboard available and delete the probe', function () {
    $root = dashboardUser(Role::Root);
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('put')->once()->andReturn(true);
    $disk->shouldReceive('get')->once()->andThrow(new RuntimeException('private storage path'));
    $disk->shouldReceive('delete')->once()->andReturn(true);
    Storage::shouldReceive('disk')->with('public')->andReturn($disk);
    $this->app->bind(DiagnoseSetup::class, DashboardDiagnoseSetup::class);

    $response = $this->actingAs($root)->get(route('dashboard'));

    $response->assertOk()->assertSee('Con errores')->assertDontSee('private storage path');
});
