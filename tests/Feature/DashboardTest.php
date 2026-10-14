<?php

use App\Enums\Role;
use App\Livewire\Dashboard;
use App\Models\User;
use App\Models\UserInvitation;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
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

test('ordinary users see their account, notifications and support shortcuts without administrative data', function () {
    $user = User::factory()->create(['name' => '<script>alert(1)</script>']);
    dashboardUser(Role::Admin);
    UserInvitation::factory()->create(['invited_by' => $user->id]);
    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\SupportTicketUpdated',
        'data' => [],
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('Notificaciones sin leer')
        ->assertSee(route('notifications.index'))
        ->assertSee(route('profile.edit'))
        ->assertSee(route('security.edit'))
        ->assertSee(route('support.index'))
        ->assertDontSee('Usuarios totales')
        ->assertDontSee('Usuarios sin verificar')
        ->assertDontSee('Administradores')
        ->assertDontSee('Invitaciones pendientes')
        ->assertDontSee('Herramientas de la app')
        ->assertDontSee('de 5 verificadas')
        ->assertDontSee(route('admin.users.index'))
        ->assertDontSee(route('admin.setup.index'));

    $component = Livewire::actingAs($user)->test(Dashboard::class);
    expect($component->viewData('userCounts'))->toBeNull()
        ->and($component->viewData('pendingInvitations'))->toBeNull()
        ->and($component->viewData('unreadNotifications'))->toBe(1);
    expect(json_encode($component->effects))->not->toContain('userCounts', 'pendingInvitations');
});

test('admin sees actionable user metrics linked to filtered lists', function () {
    $baseTotal = User::count();
    $baseActive = User::whereNull('suspended_at')->count();
    $baseSuspended = User::whereNotNull('suspended_at')->count();
    $baseUnverified = User::whereNull('email_verified_at')->count();
    $baseRecent = User::where('created_at', '>=', now()->subDays(7))->count();
    $baseAdmins = User::role(Role::Admin->value)->count();

    $admin = dashboardUser(Role::Admin);
    dashboardUser();
    $suspended = dashboardUser();
    $suspended->forceFill(['suspended_at' => now()])->save();
    $unverified = dashboardUser();
    $unverified->forceFill(['email_verified_at' => null])->save();
    dashboardUser(Role::Root);
    dashboardUser(Role::Admin);
    UserInvitation::factory()->create(['invited_by' => $admin->id]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertSee('Usuarios totales')
        ->assertSee('Usuarios activos')
        ->assertSee('Usuarios suspendidos')
        ->assertSee('Usuarios sin verificar')
        ->assertSee('Administradores')
        ->assertSee('Altas (7 días)')
        ->assertSee('Invitaciones pendientes')
        ->assertSee(route('admin.users.index', ['status' => 'active']))
        ->assertSee(route('admin.users.index', ['status' => 'suspended']))
        ->assertSee(route('admin.users.index', ['verified' => 'unverified']))
        ->assertSee(route('admin.users.index', ['role' => 'admin']))
        ->assertSee(route('admin.users.invitations'))
        ->assertSee(route('admin.users.create'))
        ->assertSee(route('support.index'))
        ->assertDontSee('Herramientas de la app')
        ->assertDontSee('de 5 verificadas')
        ->assertDontSee(route('admin.setup.index'))
        ->assertDontSee(route('admin.settings.edit'));

    $component = Livewire::actingAs($admin)->test(Dashboard::class);
    $counts = $component->viewData('userCounts');
    expect($counts['total'])->toBe($baseTotal + 6)
        ->and($counts['active'])->toBe($baseActive + 5)
        ->and($counts['suspended'])->toBe($baseSuspended + 1)
        ->and($counts['unverified'])->toBe($baseUnverified + 1)
        ->and($counts['admins'])->toBe($baseAdmins + 2)
        ->and($counts['recent'])->toBe($baseRecent + 6)
        ->and($component->viewData('pendingInvitations'))->toBe(1);
});

test('admin sees a truthful empty invitation state', function () {
    $admin = dashboardUser(Role::Admin);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertSee('No hay invitaciones pendientes de aceptación.');
});

test('admin does not see root-exclusive tool cards', function () {
    $admin = dashboardUser(Role::Admin);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Herramientas de la app')
        ->assertDontSee('Audita los cambios administrativos en toda la aplicación.')
        ->assertSee('Usuarios totales');
});

test('root sees administration and app tool cards instead of metric summaries', function () {
    $root = dashboardUser(Role::Root);

    $response = $this->actingAs($root)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Administración')
        ->assertSee('Herramientas de la app')
        ->assertSee('Revisa y responde las solicitudes de soporte de todos los usuarios.')
        ->assertSee('Gestiona cuentas, roles e invitaciones.')
        ->assertSee('Audita los cambios administrativos en toda la aplicación.')
        ->assertSee('Configura la identidad, la marca y las opciones del sitio.')
        ->assertSee('Verifica que la instalación esté configurada por completo.')
        ->assertSee('Revisa el estado y la configuración del proveedor de IA.')
        ->assertSee('Conversa con los usuarios y sigue los comunicados.')
        ->assertSee('Mantén conversaciones interactivas con el asistente de IA.')
        ->assertSee('Consulta tu propia base de conocimiento con respuestas citadas.')
        ->assertSee('Extrae datos estructurados de documentos.')
        ->assertDontSee('Usuarios totales')
        ->assertDontSee('Invitaciones pendientes')
        ->assertDontSee('de 5 verificadas')
        ->assertDontSee('Consumo de IA este mes')
        ->assertDontSee('Tu cuenta');

    $component = Livewire::actingAs($root)->test(Dashboard::class);
    expect($component->viewData('isRoot'))->toBeTrue()
        ->and($component->viewData('userCounts'))->toBeNull()
        ->and(json_encode($component->effects))->not->toContain('userCounts', 'pendingInvitations');
});

test('removing the root role between Livewire requests hides the root tool cards', function () {
    $root = dashboardUser(Role::Root);
    $component = Livewire::actingAs($root)->test(Dashboard::class)->assertSee('Herramientas de la app');

    $root->removeRole(Role::Root->value);

    $component->call('$refresh')
        ->assertDontSee('Herramientas de la app')
        ->assertDontSee('Revisa y responde las solicitudes de soporte de todos los usuarios.')
        ->assertSee('Tu cuenta');
    expect($component->viewData('isRoot'))->toBeFalse();
});
