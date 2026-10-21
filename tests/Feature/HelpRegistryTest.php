<?php

use App\Enums\Role;
use App\Models\User;
use App\Support\Help\HelpRegistry;
use Database\Seeders\RolePermissionSeeder;
use Tests\Fixtures\Help\HelpTestServiceProvider;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    app('translator')->addLines([
        'help.test.getting_started.title' => 'Primeros pasos',
        'help.test.getting_started.summary' => 'Aprende a navegar por la aplicación.',
        'help.test.getting_started.body' => "## Navegación\n\nUsa el menú lateral para moverte.",
        'help.test.users.title' => 'Gestión de usuarios',
        'help.test.users.summary' => 'Invita y administra cuentas.',
        'help.test.users.body' => 'Solo el rol **admin** puede gestionar usuarios.',
        'help.test.faq.title' => 'Preguntas frecuentes',
        'help.test.faq.summary' => 'Respuestas rápidas.',
        'help.test.faq.body' => "Texto con <script>alert('x')</script> y un [enlace](javascript:alert(1)).",
        'help.test.groups.getting_started' => 'Primeros pasos',
        'help.test.groups.administration' => 'Administración',
        'help.test.groups.faq' => 'Preguntas frecuentes',
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
        'help.test.faq.body' => "Text with <script>alert('x')</script> and a [link](javascript:alert(1)).",
        'help.test.groups.getting_started' => 'Getting started',
        'help.test.groups.administration' => 'Administration',
        'help.test.groups.faq' => 'Frequently asked questions',
    ], 'en');
});

function helpEntry(array $overrides = []): array
{
    return array_replace([
        'type' => 'article',
        'group' => 'help.test.groups.getting_started',
        'title' => 'help.test.getting_started.title',
        'summary' => 'help.test.getting_started.summary',
        'body' => 'help.test.getting_started.body',
    ], $overrides);
}

function registerDemoEntries(HelpRegistry $registry): void
{
    $registry->register('demo', [
        'demo.users' => helpEntry([
            'group' => 'help.test.groups.administration',
            'title' => 'help.test.users.title',
            'summary' => 'help.test.users.summary',
            'body' => 'help.test.users.body',
            'roles' => ['admin'],
            'order' => 2,
        ]),
        'demo.getting-started' => helpEntry([
            'order' => 1,
            'route' => 'dashboard',
        ]),
        'demo.faq' => helpEntry([
            'type' => 'faq',
            'group' => 'help.test.groups.faq',
            'title' => 'help.test.faq.title',
            'summary' => 'help.test.faq.summary',
            'body' => 'help.test.faq.body',
            'order' => 3,
        ]),
    ]);
}

it('registers an entry with sensible defaults', function () {
    $registry = new HelpRegistry;
    $registry->register('demo', ['demo.getting-started' => helpEntry()]);

    $entry = $registry->all(User::factory()->create())[0];

    expect($entry['source'])->toBe('demo')
        ->and($entry['slug'])->toBe('demo.getting-started')
        ->and($entry['type'])->toBe('article')
        ->and($entry['roles'])->toBe([])
        ->and($entry['order'])->toBe(0)
        ->and($entry['route'])->toBeNull();
});

it('rejects an empty source, an unprefixed slug and a duplicate', function () {
    $registry = new HelpRegistry;

    expect(fn () => $registry->register('', []))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $registry->register('demo', ['getting-started' => helpEntry()]))
        ->toThrow(InvalidArgumentException::class);

    $registry->register('demo', ['demo.getting-started' => helpEntry()]);

    expect(fn () => $registry->register('demo', ['demo.getting-started' => helpEntry()]))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects an unknown type, a missing field, an unknown role and a non-integer order', function () {
    $registry = new HelpRegistry;

    expect(fn () => $registry->register('demo', ['demo.entry' => helpEntry(['type' => 'guide'])]))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $registry->register('demo', ['demo.entry' => helpEntry(['summary' => ''])]))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $registry->register('demo', ['demo.entry' => helpEntry(['roles' => ['editor']])]))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $registry->register('demo', ['demo.entry' => helpEntry(['order' => '1'])]))
        ->toThrow(InvalidArgumentException::class);
});

it('filters by role, lets root see everything and sorts by order', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $root = User::factory()->create();
    $root->assignRole(Role::Root);

    $registry = new HelpRegistry;
    registerDemoEntries($registry);

    expect(array_column($registry->all($user), 'slug'))
        ->toBe(['demo.getting-started', 'demo.faq'])
        ->and(array_column($registry->all($admin), 'slug'))
        ->toBe(['demo.getting-started', 'demo.users', 'demo.faq'])
        ->and(array_column($registry->all($root), 'slug'))
        ->toBe(['demo.getting-started', 'demo.users', 'demo.faq']);

    expect($registry->find('demo.users', $user))->toBeNull()
        ->and($registry->find('demo.users', $admin)['title'])->toBe('help.test.users.title')
        ->and($registry->find('demo.missing', $root))->toBeNull();
});

it('always ranks customization packages above the starterkit', function () {
    $registry = new HelpRegistry;

    $registry->register(HelpRegistry::STARTERKIT_SOURCE, [
        'lnx.first' => helpEntry(['order' => 1]),
        'lnx.second' => helpEntry(['order' => 2]),
    ]);

    $registry->register('luz', [
        'luz.second' => helpEntry(['order' => 20]),
        'luz.first' => helpEntry(['order' => 10]),
    ]);

    expect(array_column($registry->all(User::factory()->create()), 'slug'))
        ->toBe(['luz.first', 'luz.second', 'lnx.first', 'lnx.second']);
});

it('searches the resolved title, summary and body case-insensitively and respects roles', function () {
    $registry = new HelpRegistry;
    registerDemoEntries($registry);
    $user = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);

    expect(array_column($registry->search('NAVEGAR', $user), 'slug'))->toBe(['demo.getting-started'])
        ->and(array_column($registry->search('menú lateral', $user), 'slug'))->toBe(['demo.getting-started'])
        ->and(array_column($registry->search('GESTIÓN', $user), 'slug'))->toBe([])
        ->and(array_column($registry->search('GESTIÓN', $admin), 'slug'))->toBe(['demo.users']);

    app()->setLocale('en');

    expect(array_column($registry->search('navigate', $user), 'slug'))->toBe(['demo.getting-started'])
        ->and(array_column($registry->search('sidebar', $user), 'slug'))->toBe(['demo.getting-started']);
});

it('renders markdown without raw html or unsafe links', function () {
    $registry = new HelpRegistry;
    registerDemoEntries($registry);
    $faq = $registry->find('demo.faq', User::factory()->create());

    $html = $registry->bodyHtml($faq);

    expect($html)->toContain('<p>')
        ->and($html)->not->toContain('<script>')
        ->and($html)->not->toContain('javascript:');
});

it('resolves the optional route only when it exists', function () {
    $registry = new HelpRegistry;
    registerDemoEntries($registry);
    $user = User::factory()->create();

    expect($registry->routeUrl($registry->find('demo.getting-started', $user)))->toBe(route('dashboard'))
        ->and($registry->routeUrl(['route' => 'missing.route']))->toBeNull();
});

it('receives entries from a package provider during boot', function () {
    app()->register(HelpTestServiceProvider::class);

    $root = User::factory()->create();
    $root->assignRole(Role::Root);

    $entries = collect(app(HelpRegistry::class)->all($root));
    $slugs = array_column($entries->all(), 'slug');

    expect($entries->firstWhere('slug', 'demo.getting-started'))->not->toBeNull()
        ->and($entries->firstWhere('slug', 'demo.users')['roles'])->toBe(['admin'])
        ->and(array_slice($slugs, 0, 3))->toBe(['demo.getting-started', 'demo.users', 'demo.faq'])
        ->and($slugs[3])->toBe('lnx.getting-started');
});
