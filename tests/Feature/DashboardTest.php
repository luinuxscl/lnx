<?php

use App\Enums\Role;
use App\Livewire\Dashboard;
use App\Models\User;
use App\Models\UserInvitation;
use Database\Seeders\RolePermissionSeeder;
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

/**
 * @return list<string>
 */
function dashboardWidgetIds($component): array
{
    return collect($component->viewData('sections'))
        ->flatMap(fn (array $group) => collect($group['widgets'])
            ->map(fn (array $entry) => $entry['widget']->id()))
        ->values()
        ->all();
}

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('ordinary users see their account shortcuts without administrative data', function () {
    $user = User::factory()->create(['name' => '<script>alert(1)</script>']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('Accesos')
        ->assertSee('Tu cuenta')
        ->assertSee(route('profile.edit'))
        ->assertSee(route('security.edit'))
        ->assertSee(route('support.index'))
        ->assertDontSee('Usuarios totales')
        ->assertDontSee('Herramientas de root')
        ->assertDontSee(route('admin.users.index'));

    $component = Livewire::actingAs($user)->test(Dashboard::class);
    $ids = dashboardWidgetIds($component);

    expect($ids)->toContain('lnx.account')
        ->and($ids)->not->toContain('lnx.users', 'lnx.root_tools');
});

test('admin sees actionable user metrics linked to filtered lists', function () {
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

    $response->assertSee('Datos')
        ->assertSee('Usuarios totales')
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
        ->assertSee(route('admin.users.index', ['invite' => 1]))
        ->assertSee(route('support.index'))
        ->assertDontSee('Herramientas de root')
        ->assertDontSee(route('admin.setup.index'))
        ->assertDontSee(route('admin.settings.edit'));

    $component = Livewire::actingAs($admin)->test(Dashboard::class);
    $ids = dashboardWidgetIds($component);

    expect($ids)->toContain('lnx.users', 'lnx.account')
        ->and($ids)->not->toContain('lnx.root_tools');
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
        ->assertDontSee('Herramientas de root')
        ->assertDontSee('Audita los cambios administrativos en toda la aplicación.')
        ->assertSee('Usuarios totales');
});

test('root sees the administration and app tools instead of metric summaries', function () {
    $root = dashboardUser(Role::Root);

    $response = $this->actingAs($root)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Accesos')
        ->assertSee('Herramientas de root')
        ->assertSee('Revisa y responde las solicitudes de soporte de todos los usuarios.')
        ->assertSee('Gestiona cuentas, roles e invitaciones.')
        ->assertSee('Audita los cambios administrativos en toda la aplicación.')
        ->assertSee('Configura la identidad, la marca y las opciones del sitio.')
        ->assertSee('Verifica que la instalación esté configurada por completo.')
        ->assertSee('Revisa el estado y la configuración del proveedor de IA.')
        ->assertSee('Consulta tu propia base de conocimiento con respuestas citadas.')
        ->assertSee('Extrae datos estructurados de documentos.')
        ->assertSee('Tu cuenta')
        ->assertDontSee('Usuarios totales')
        ->assertDontSee('Invitaciones pendientes');

    $component = Livewire::actingAs($root)->test(Dashboard::class);
    $ids = dashboardWidgetIds($component);

    expect($ids)->toContain('lnx.root_tools', 'lnx.account')
        ->and($ids)->not->toContain('lnx.users');
});

test('removing the root role between Livewire requests hides the root tool cards', function () {
    $root = dashboardUser(Role::Root);
    $component = Livewire::actingAs($root)->test(Dashboard::class)->assertSee('Herramientas de root');

    $root->removeRole(Role::Root->value);

    $component->call('$refresh')
        ->assertDontSee('Herramientas de root')
        ->assertDontSee('Revisa y responde las solicitudes de soporte de todos los usuarios.')
        ->assertSee('Tu cuenta');
});
