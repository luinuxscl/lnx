<?php

use App\Actions\HelpSync;
use App\Enums\AdministrativeAction;
use App\Models\AdministrativeActivity;
use App\Models\HelpEntry;
use App\Models\HelpGroup;
use App\Support\Help\HelpRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Tests\Fixtures\Help\HelpTestServiceProvider;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app()->register(HelpTestServiceProvider::class);
});

it('materializes every declared entry and group', function () {
    $summary = app(HelpSync::class)->handle();

    $declared = app(HelpRegistry::class)->declared();

    expect(HelpEntry::query()->count())->toBe(count($declared))
        ->and($summary['created'])->toBe(count($declared))
        ->and($summary['hidden'])->toBe(0);

    $entry = HelpEntry::query()->where('slug', 'demo.users')->firstOrFail();

    expect($entry->source)->toBe('demo')
        ->and($entry->type)->toBe('article')
        ->and($entry->title_key)->toBe('help.test.users.title')
        ->and($entry->roleList())->toBe(['admin'])
        ->and($entry->overridden)->toBeFalse()
        ->and($entry->enabled)->toBeTrue()
        ->and($entry->group->group_key)->toBe('help.test.groups.administration');

    expect(AdministrativeActivity::query()->where('action', AdministrativeAction::HelpEntryCreated)->count())->toBe(count($declared));
});

it('is idempotent', function () {
    $sync = app(HelpSync::class);

    $first = $sync->handle();
    $snapshot = HelpEntry::query()->orderBy('id')->get()->map->only(['slug', 'position', 'help_group_id'])->all();
    $activity = AdministrativeActivity::query()->count();

    $second = $sync->handle();

    expect($second)->toBe(['created' => 0, 'updated' => 0, 'hidden' => 0])
        ->and($first['created'])->toBeGreaterThan(0)
        ->and(HelpEntry::query()->count())->toBe($first['created'])
        ->and(HelpEntry::query()->orderBy('id')->get()->map->only(['slug', 'position', 'help_group_id'])->all())->toBe($snapshot)
        ->and(AdministrativeActivity::query()->count())->toBe($activity);
});

it('never overwrites a root edit nor resurrects a hidden entry', function () {
    app(HelpSync::class)->handle();

    $entry = HelpEntry::query()->where('slug', 'demo.users')->firstOrFail();
    $entry->update([
        'title_es' => 'Usuarios (editado)',
        'title_en' => 'Users (edited)',
        'body_es' => 'Contenido propio de root.',
        'body_en' => 'Root-owned content.',
        'roles' => [],
        'enabled' => false,
        'overridden' => true,
    ]);

    app(HelpSync::class)->handle();

    $entry->refresh();

    expect($entry->title_es)->toBe('Usuarios (editado)')
        ->and($entry->body_en)->toBe('Root-owned content.')
        ->and($entry->roleList())->toBe([])
        ->and($entry->enabled)->toBeFalse()
        ->and($entry->overridden)->toBeTrue()
        ->and($entry->title_key)->toBe('help.test.users.title');
});

it('updates package-owned content while preserving the root override', function () {
    app(HelpSync::class)->handle();

    $entry = HelpEntry::query()->where('slug', 'demo.users')->firstOrFail();
    $entry->update(['title_key' => 'help.test.old.title', 'overridden' => true]);

    app(HelpSync::class)->handle();

    expect($entry->refresh()->title_key)->toBe('help.test.users.title');
});

it('hides entries whose declaration disappeared without deleting them', function () {
    app(HelpSync::class)->handle();

    $group = HelpGroup::query()->firstOrFail();
    $orphan = HelpEntry::create([
        'help_group_id' => $group->id,
        'source' => 'old',
        'slug' => 'old.removed-guide',
        'type' => 'article',
        'title_key' => 'old.title',
        'summary_key' => 'old.summary',
        'body_key' => 'old.body',
        'roles' => [],
        'position' => 99,
        'enabled' => true,
        'overridden' => false,
    ]);

    $summary = app(HelpSync::class)->handle();

    expect($summary['hidden'])->toBe(1)
        ->and($orphan->refresh()->enabled)->toBeFalse()
        ->and(HelpEntry::query()->where('slug', 'old.removed-guide')->exists())->toBeTrue()
        ->and(AdministrativeActivity::query()->where('action', AdministrativeAction::HelpEntryHidden)->exists())->toBeTrue();
});

it('runs help:sync twice without duplicating anything', function () {
    Artisan::call('help:sync');
    Artisan::call('help:sync');

    $output = Artisan::output();

    expect($output)->toContain('0 created, 0 updated, 0 hidden')
        ->and(HelpEntry::query()->count())->toBe(count(app(HelpRegistry::class)->declared()));
});
