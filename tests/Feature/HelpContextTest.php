<?php

use App\Enums\Role;
use App\Models\User;
use App\Notifications\SupportTicketUpdated;
use App\Support\Help\HelpRegistry;
use App\Support\Notifications\NotificationTypeRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Tests\Fixtures\Notifications\DocumentReadyNotification;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    app(HelpRegistry::class)->register('demo', [
        'demo.getting-started' => [
            'type' => 'article',
            'group' => 'Primeros pasos',
            'title' => 'Primeros pasos',
            'summary' => 'Aprende a navegar.',
            'body' => 'Contenido público.',
            'order' => 1,
        ],
        'demo.users' => [
            'type' => 'article',
            'group' => 'Administración',
            'title' => 'Gestión de usuarios',
            'summary' => 'Invita y administra cuentas.',
            'body' => 'Solo administradores.',
            'roles' => ['admin'],
            'order' => 2,
        ],
    ]);
});

function helpContextUser(?Role $role = null): User
{
    $user = User::factory()->create();

    if ($role !== null) {
        $user->assignRole($role);
    }

    return $user;
}

it('links to a visible entry with an accessible label and a tooltip', function () {
    $this->actingAs(helpContextUser());

    $this->blade('<x-help-topic slug="demo.getting-started" />')
        ->assertSee('topic=demo.getting-started', false)
        ->assertSee('aria-label="Leer la guía: Primeros pasos"', false)
        ->assertSee('Primeros pasos');
});

it('renders nothing when the slug does not exist', function () {
    $this->actingAs(helpContextUser());

    $this->blade('<x-help-topic slug="demo.missing" />')
        ->assertDontSee('help?topic', false)
        ->assertDontSee('Leer la guía', false);
});

it('renders nothing for a guest', function () {
    $this->blade('<x-help-topic slug="demo.getting-started" />')
        ->assertDontSee('help?topic', false);
});

it('hides a restricted entry from identities that cannot read it', function () {
    $this->actingAs(helpContextUser());

    $this->blade('<x-help-topic slug="demo.users" />')
        ->assertDontSee('topic=demo.users', false);

    $this->actingAs(helpContextUser(Role::Admin));

    $this->blade('<x-help-topic slug="demo.users" />')
        ->assertSee('topic=demo.users', false)
        ->assertSee('Leer la guía: Gestión de usuarios');
});

it('shows the guide link on the screens it explains', function () {
    $admin = helpContextUser(Role::Admin);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('topic=lnx.users-invitations', false);

    $user = helpContextUser();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('topic=lnx.profile-security', false);
});

it('lets a module declare the help entry related to its notifications', function () {
    app(NotificationTypeRegistry::class)->register('demo', [
        DocumentReadyNotification::class => [
            'message' => fn (DatabaseNotification $notification): string => 'Documento listo.',
            'help' => fn (DatabaseNotification $notification): string => 'demo.getting-started',
        ],
    ]);

    $user = helpContextUser();
    $user->notify(new DocumentReadyNotification(7, 'Resultados'));

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('topic=demo.getting-started', false)
        ->assertSee('Leer la guía');
});

it('hides the notification help link when the entry is restricted', function () {
    app(NotificationTypeRegistry::class)->register('demo', [
        DocumentReadyNotification::class => [
            'message' => fn (DatabaseNotification $notification): string => 'Documento listo.',
            'help' => fn (DatabaseNotification $notification): string => 'demo.users',
        ],
    ]);

    $user = helpContextUser();
    $user->notify(new DocumentReadyNotification(7, 'Resultados'));

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertDontSee('topic=demo.users', false);
});

it('links the kit support notifications to the support guide', function () {
    $user = helpContextUser();
    $user->notify(new SupportTicketUpdated(1, 'reply'));

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('topic=lnx.feedback-support', false);
});

it('rejects a help declaration that is not callable', function () {
    app(NotificationTypeRegistry::class)->register('demo', [
        DocumentReadyNotification::class => [
            'message' => fn (): string => 'Documento listo.',
            'help' => 'demo.getting-started',
        ],
    ]);
})->throws(InvalidArgumentException::class);
