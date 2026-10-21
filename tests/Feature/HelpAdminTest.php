<?php

use App\Actions\HelpSync;
use App\Enums\AdministrativeAction;
use App\Enums\Role;
use App\Livewire\Help\Admin\Index;
use App\Models\AdministrativeActivity;
use App\Models\HelpEntry;
use App\Models\User;
use App\Support\Help\HelpCatalog;
use App\Support\Help\HelpRegistry;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Tests\Fixtures\Help\HelpTestServiceProvider;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app()->register(HelpTestServiceProvider::class);

    app('translator')->addLines([
        'help.test.getting_started.title' => 'Primeros pasos',
        'help.test.getting_started.summary' => 'Aprende a navegar por la aplicación.',
        'help.test.getting_started.body' => "## Navegación\n\nUsa el menú lateral para moverte.",
        'help.test.users.title' => 'Gestión de usuarios',
        'help.test.users.summary' => 'Invita y administra cuentas.',
        'help.test.users.body' => 'Solo el rol **admin** puede gestionar usuarios.',
        'help.test.faq.title' => 'Preguntas frecuentes',
        'help.test.faq.summary' => 'Respuestas rápidas.',
        'help.test.faq.body' => 'Puedes restablecer tu contraseña.',
    ], 'es');

    app('translator')->addLines([
        'help.test.getting_started.title' => 'Getting started',
        'help.test.getting_started.summary' => 'Learn how to navigate the application.',
        'help.test.getting_started.body' => "## Navigation\n\nUse the sidebar to move around.",
        'help.test.users.title' => 'User management',
        'help.test.users.summary' => 'Invite and manage accounts.',
        'help.test.users.body' => 'Only the **admin** role can manage users.',
        'help.test.faq.title' => 'Frequently asked questions',
        'help.test.faq.summary' => 'Quick answers.',
        'help.test.faq.body' => 'You can reset your password.',
    ], 'en');
});

function helpAdminUser(?Role $role = null): User
{
    $user = User::factory()->create();

    if ($role !== null) {
        $user->assignRole($role);
    }

    return $user;
}

it('restricts the editor to root', function () {
    $this->get(route('admin.help.index'))->assertRedirect(route('login'));

    $this->actingAs(helpAdminUser())->get(route('admin.help.index'))->assertForbidden();
    $this->actingAs(helpAdminUser(Role::Admin))->get(route('admin.help.index'))->assertForbidden();

    app(HelpSync::class)->handle();

    $this->actingAs(helpAdminUser(Role::Root))->get(route('admin.help.index'))
        ->assertOk()
        ->assertSee('Gestión de usuarios');
});

it('lets root edit an entry in both languages without help:sync overwriting it', function () {
    app(HelpSync::class)->handle();

    $root = helpAdminUser(Role::Root);
    $entry = HelpEntry::query()->where('slug', 'demo.users')->firstOrFail();

    Livewire::actingAs($root)->test(Index::class)
        ->call('openEditor', $entry->id)
        ->assertSet('titleEs', 'Gestión de usuarios')
        ->assertSet('titleEn', 'User management')
        ->set('titleEs', 'Guía editada')
        ->set('titleEn', 'Edited guide')
        ->set('summaryEs', 'Resumen editado.')
        ->set('summaryEn', 'Edited summary.')
        ->set('bodyEs', 'Contenido editado por root.')
        ->set('bodyEn', 'Content edited by root.')
        ->set('roles', ['admin'])
        ->call('save')
        ->assertHasNoErrors();

    $entry->refresh();

    expect($entry->overridden)->toBeTrue()
        ->and($entry->title_es)->toBe('Guía editada')
        ->and($entry->body_en)->toBe('Content edited by root.');

    app(HelpSync::class)->handle();

    expect($entry->refresh()->title_es)->toBe('Guía editada')
        ->and($entry->overridden)->toBeTrue();

    app()->setLocale('en');

    expect((string) __($entry->content('title')))->toBe('Edited guide');

    expect(AdministrativeActivity::query()
        ->where('action', AdministrativeAction::HelpEntryUpdated)
        ->where('actor_id', $root->id)
        ->exists())->toBeTrue();
});

it('restores an entry to the package default', function () {
    app(HelpSync::class)->handle();

    $root = helpAdminUser(Role::Root);
    $entry = HelpEntry::query()->where('slug', 'demo.users')->firstOrFail();
    $entry->update([
        'title_es' => 'Guía editada',
        'title_en' => 'Edited guide',
        'summary_es' => 'Resumen editado.',
        'summary_en' => 'Edited summary.',
        'body_es' => 'Contenido editado.',
        'body_en' => 'Edited content.',
        'overridden' => true,
    ]);

    Livewire::actingAs($root)->test(Index::class)
        ->call('restoreEntry', $entry->id)
        ->assertHasNoErrors();

    $entry->refresh();

    expect($entry->overridden)->toBeFalse()
        ->and($entry->title_es)->toBeNull()
        ->and($entry->title_key)->toBe('help.test.users.title');

    app()->setLocale('en');

    $resolved = app(HelpCatalog::class)->find('demo.users', $root);

    expect($resolved)->not->toBeNull()
        ->and((string) __($resolved['title']))->toBe('User management');
});

it('reorders entries and records the change', function () {
    app(HelpSync::class)->handle();

    $root = helpAdminUser(Role::Root);
    $first = HelpEntry::query()->where('slug', 'lnx.getting-started')->firstOrFail();
    $second = HelpEntry::query()->where('slug', 'lnx.dashboard')->firstOrFail();

    expect($first->position)->toBeLessThan($second->position);

    Livewire::actingAs($root)->test(Index::class)
        ->call('moveEntry', $second->id, 'up')
        ->assertHasNoErrors();

    expect($second->refresh()->position)->toBeLessThan($first->refresh()->position)
        ->and($second->overridden)->toBeTrue()
        ->and($first->overridden)->toBeTrue();

    app(HelpSync::class)->handle();

    expect($second->refresh()->position)->toBeLessThan($first->refresh()->position);

    expect(AdministrativeActivity::query()
        ->where('action', AdministrativeAction::HelpEntryUpdated)
        ->where('actor_id', $root->id)
        ->exists())->toBeTrue();
});

it('hides and republishes an entry', function () {
    app(HelpSync::class)->handle();

    $root = helpAdminUser(Role::Root);
    $entry = HelpEntry::query()->where('slug', 'lnx.dashboard')->firstOrFail();

    Livewire::actingAs($root)->test(Index::class)
        ->call('toggleEntry', $entry->id)
        ->assertHasNoErrors();

    expect($entry->refresh()->enabled)->toBeFalse()
        ->and($entry->overridden)->toBeTrue();

    app(HelpSync::class)->handle();

    expect($entry->refresh()->enabled)->toBeFalse();

    Livewire::actingAs($root)->test(Index::class)
        ->call('toggleEntry', $entry->id)
        ->assertHasNoErrors();

    expect($entry->refresh()->enabled)->toBeTrue();
});

it('publishes an available entry before the next synchronization', function () {
    app(HelpSync::class)->handle();

    app(HelpRegistry::class)->register('demo2', [
        'demo2.extra' => [
            'type' => 'article',
            'group' => 'help.test.groups.getting_started',
            'title' => 'help.test.getting_started.title',
            'summary' => 'help.test.getting_started.summary',
            'body' => 'help.test.getting_started.body',
            'order' => 50,
        ],
    ]);

    $root = helpAdminUser(Role::Root);

    Livewire::actingAs($root)->test(Index::class)
        ->set('status', 'available')
        ->assertSee('demo2.extra')
        ->call('publishEntry', 'demo2.extra')
        ->assertHasNoErrors();

    $entry = HelpEntry::query()->where('slug', 'demo2.extra')->firstOrFail();

    expect($entry->source)->toBe('demo2')
        ->and($entry->overridden)->toBeFalse()
        ->and($entry->enabled)->toBeTrue();

    expect(AdministrativeActivity::query()
        ->where('action', AdministrativeAction::HelpEntryCreated)
        ->where('actor_id', $root->id)
        ->exists())->toBeTrue();
});
