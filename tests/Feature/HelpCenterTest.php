<?php

use App\Enums\Role;
use App\Livewire\Help\Index;
use App\Models\User;
use Database\Seeders\NavigationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Tests\Fixtures\Help\HelpTestServiceProvider;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, NavigationSeeder::class]);
    app()->register(HelpTestServiceProvider::class);

    app('translator')->addLines([
        'help.test.getting_started.title' => 'Primeros pasos',
        'help.test.getting_started.summary' => 'Aprende a navegar por la aplicación.',
        'help.test.getting_started.body' => "## Navegación\n\nUsa el menú lateral para moverte.",
        'help.test.users.title' => 'Gestión de usuarios',
        'help.test.users.summary' => 'Invita y administra cuentas.',
        'help.test.users.body' => 'Solo el rol **admin** puede gestionar usuarios.',
        'help.test.faq.title' => 'Preguntas frecuentes sobre accesos',
        'help.test.faq.summary' => 'Respuestas rápidas sobre el acceso.',
        'help.test.faq.body' => 'Puedes restablecer tu contraseña desde el inicio de sesión.',
        'help.test.groups.getting_started' => 'Primeros pasos',
        'help.test.groups.administration' => 'Administración',
        'help.test.groups.faq' => 'Acceso y cuenta',
    ], 'es');

    app('translator')->addLines([
        'help.test.getting_started.title' => 'Getting started',
        'help.test.getting_started.summary' => 'Learn how to navigate the application.',
        'help.test.getting_started.body' => "## Navigation\n\nUse the sidebar to move around.",
        'help.test.users.title' => 'User management',
        'help.test.users.summary' => 'Invite and manage accounts.',
        'help.test.users.body' => 'Only the **admin** role can manage users.',
        'help.test.faq.title' => 'Frequently asked questions about access',
        'help.test.faq.summary' => 'Quick answers about access.',
        'help.test.faq.body' => 'You can reset your password from the sign-in page.',
        'help.test.groups.getting_started' => 'Getting started',
        'help.test.groups.administration' => 'Administration',
        'help.test.groups.faq' => 'Access and account',
    ], 'en');
});

function helpPageUser(?Role $role = null): User
{
    $user = User::factory()->create();

    if ($role !== null) {
        $user->assignRole($role);
    }

    return $user;
}

it('requires an authenticated account', function () {
    $this->get(route('help.index'))->assertRedirect(route('login'));
});

it('shows only the entries the user may read and keeps restricted deep links hidden', function () {
    $user = helpPageUser();

    $this->actingAs($user)->get(route('help.index'))
        ->assertOk()
        ->assertSee('Primeros pasos')
        ->assertSee('Aprende a navegar por la aplicación.')
        ->assertDontSee('Gestión de usuarios');

    $this->actingAs($user)->get(route('help.index', ['topic' => 'demo.users']))
        ->assertNotFound();

    $admin = helpPageUser(Role::Admin);

    $this->actingAs($admin)->get(route('help.index'))
        ->assertOk()
        ->assertSee('Gestión de usuarios');

    $this->actingAs($admin)->get(route('help.index', ['topic' => 'demo.users']))
        ->assertOk()
        ->assertSee('Solo el rol')
        ->assertSee('<strong>admin</strong>', false);
});

it('lets root read every declared entry', function () {
    $root = helpPageUser(Role::Root);

    $this->actingAs($root)->get(route('help.index'))
        ->assertOk()
        ->assertSee('Primeros pasos')
        ->assertSee('Gestión de usuarios')
        ->assertSeeInOrder(['demo.getting-started', 'lnx.getting-started']);

    $this->actingAs($root)->get(route('help.index', ['tab' => 'faq', 'topic' => 'demo.users']))
        ->assertOk()
        ->assertSee('Gestión de usuarios');
});

it('restores tabs, search and topic from the URL', function () {
    $user = helpPageUser();

    $this->actingAs($user)->get(route('help.index', ['tab' => 'faq']))
        ->assertOk()
        ->assertSee('Preguntas frecuentes sobre accesos')
        ->assertSee('Puedes restablecer tu contraseña', false)
        ->assertDontSee('Primeros pasos');

    $this->actingAs($user)->get(route('help.index', ['search' => 'NAVEGAR']))
        ->assertOk()
        ->assertSee('Primeros pasos')
        ->assertDontSee('Preguntas frecuentes sobre accesos');

    $this->actingAs($user)->get(route('help.index', ['tab' => 'guides', 'topic' => 'demo.getting-started']))
        ->assertOk()
        ->assertSee('Usa el menú lateral para moverte.', false)
        ->assertSee('Ir a la función');
});

it('shares search state through the component URL attribute', function () {
    $user = helpPageUser();

    Livewire::actingAs($user)->test(Index::class)
        ->set('search', 'navegar')
        ->assertSet('search', 'navegar')
        ->assertSee('Primeros pasos')
        ->assertDontSee('Gestión de usuarios');
});

it('renders the support tab without creating a second ticket flow', function () {
    $user = helpPageUser();

    $this->actingAs($user)->get(route('help.index', ['tab' => 'support']))
        ->assertOk()
        ->assertSee('Crear una solicitud de soporte')
        ->assertSee('Ver mis solicitudes')
        ->assertSee(route('support.index', ['mine' => 1]), false);
});

it('exposes the help entry in the bottom menu for every audience', function (?Role $role) {
    $this->actingAs(helpPageUser($role))
        ->get(route('help.index'))
        ->assertOk()
        ->assertSee(route('help.index'), false)
        ->assertSee('Centro de ayuda');
})->with([
    'root' => [Role::Root],
    'admin' => [Role::Admin],
    'user' => [null],
]);
