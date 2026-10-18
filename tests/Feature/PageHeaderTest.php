<?php

use App\Enums\Role;
use App\Models\DocumentExtraction;
use App\Models\KnowledgeDocument;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('renders the page title as the page heading', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('<h1', false)
        ->assertSee('Usuarios');
});

it('renders the ancestor breadcrumb link on a nested page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->get(route('admin.users.invitations'))
        ->assertOk()
        ->assertSee('Invitaciones')
        ->assertSee(route('admin.users.index'), false);
});

it('renders the settings heading through the shared partial', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('<h1', false)
        ->assertSee('Ajustes');
});

it('renders a single root breadcrumb on a first-level page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="page-breadcrumbs"', false)
        ->assertSee('Dashboard');
});

it('renders the full navigable chain on a nested page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $target))
        ->assertOk()
        ->assertSee('data-test="page-breadcrumbs"', false)
        ->assertSee(route('admin.users.index'), false)
        ->assertSee('Editar usuario');
});

it('renders the dynamic current page as the last breadcrumb', function () {
    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    $document = KnowledgeDocument::create([
        'user_id' => $root->id,
        'title' => 'Manual de operaciones',
        'source_type' => 'text',
        'content' => 'Contenido de prueba para el breadcrumb.',
        'status' => 'indexed',
    ]);

    $this->actingAs($root)
        ->get(route('knowledge.show', $document))
        ->assertOk()
        ->assertSee('data-test="page-breadcrumbs"', false)
        ->assertSee(route('knowledge.index'), false)
        ->assertSee('Manual de operaciones');
});

it('renders breadcrumbs on every page', function () {
    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);
    $user = User::factory()->create();

    $document = KnowledgeDocument::create([
        'user_id' => $root->id,
        'title' => 'Breadcrumb document',
        'source_type' => 'text',
        'content' => 'Contenido de prueba.',
        'status' => 'indexed',
    ]);

    $extraction = DocumentExtraction::factory()->create(['user_id' => $root->id]);

    $ticket = SupportTicket::factory()->for($root)->create(['title' => 'Breadcrumb ticket']);

    $pages = [
        [route('dashboard'), $user],
        [route('admin.users.index'), $root],
        [route('admin.users.invitations'), $root],
        [route('admin.users.edit', $user), $root],
        [route('profile.edit'), $user],
        [route('appearance.edit'), $user],
        [route('admin.settings.edit'), $root],
        [route('knowledge.index'), $root],
        [route('knowledge.bases'), $root],
        [route('knowledge.show', $document), $root],
        [route('documents.index'), $root],
        [route('documents.show', $extraction), $root],
        [route('support.index'), $root],
        [route('support.show', $ticket), $root],
        [route('admin.activity.index'), $root],
        [route('admin.setup.index'), $root],
        [route('admin.ai.index'), $root],
        [route('admin.ai.usage'), $root],
        [route('admin.packages.index'), $root],
        [route('admin.packages.lnx-ui'), $root],
        [route('admin.navigation.index'), $root],
        [route('admin.emails.index'), $root],
        [route('notifications.index'), $user],
    ];

    if (Route::has('admin.ai.text-assistant')) {
        $pages[] = [route('admin.ai.text-assistant'), $root];
    }

    foreach ($pages as [$url, $actor]) {
        $this->actingAs($actor)
            ->get($url)
            ->assertOk()
            ->assertSee('data-test="page-breadcrumbs"', false);
    }
});
