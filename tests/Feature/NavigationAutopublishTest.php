<?php

use App\Actions\NavigationSync;
use App\Enums\NavigationAudience;
use App\Enums\NavigationSource;
use App\Models\NavigationGroup;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Support\Facades\Route;

/**
 * @param  array<string, mixed>  $tag
 */
function tagMenuRoute(string $name, array $tag): void
{
    Route::get('/testing/'.$name, fn (): string => 'ok')->name($name)->menu($tag);
}

function syncAudience(NavigationAudience $audience): void
{
    app(NavigationSync::class)->handle($audience);
}

function autopublishTag(string $group = 'Analytics', array $audiences = ['root']): array
{
    return [
        'label' => 'Reports',
        'icon' => 'chart-bar',
        'group' => $group,
        'group_icon' => 'chart-bar',
        'audiences' => $audiences,
        'auto' => true,
    ];
}

beforeEach(function () {
    app()->setLocale('en');
});

it('autopublishes a tagged route into its audience', function () {
    tagMenuRoute('test.reports', autopublishTag());

    syncAudience(NavigationAudience::Root);

    $menu = NavigationMenu::query()->where('audience', NavigationAudience::Root)->firstOrFail();
    $item = NavigationItem::query()->where('route_name', 'test.reports')->firstOrFail();

    expect($item->source)->toBe(NavigationSource::Catalog)
        ->and($item->overridden)->toBeFalse()
        ->and($item->enabled)->toBeTrue()
        ->and($item->group->navigation_menu_id)->toBe($menu->id)
        ->and($item->group->label_key)->toBe('Analytics');
});

it('is idempotent across runs', function () {
    tagMenuRoute('test.reports', autopublishTag());
    tagMenuRoute('test.orders', autopublishTag());

    syncAudience(NavigationAudience::Root);

    $items = NavigationItem::query()->orderBy('id')->get(['id', 'position', 'navigation_group_id'])->toArray();
    $groups = NavigationGroup::query()->orderBy('id')->pluck('position', 'id')->toArray();

    syncAudience(NavigationAudience::Root);

    expect(NavigationItem::query()->orderBy('id')->get(['id', 'position', 'navigation_group_id'])->toArray())->toBe($items)
        ->and(NavigationGroup::query()->orderBy('id')->pluck('position', 'id')->toArray())->toBe($groups)
        ->and(NavigationItem::query()->where('route_name', 'test.reports')->count())->toBe(1);
});

it('does not overwrite a root override', function () {
    tagMenuRoute('test.reports', autopublishTag());
    syncAudience(NavigationAudience::Root);

    $item = NavigationItem::query()->where('route_name', 'test.reports')->firstOrFail();
    $item->update(['overridden' => true, 'enabled' => false]);

    syncAudience(NavigationAudience::Root);

    expect($item->fresh()->enabled)->toBeFalse()
        ->and($item->fresh()->overridden)->toBeTrue();
});

it('re-enables a catalog item that root has not overridden', function () {
    tagMenuRoute('test.reports', autopublishTag());
    syncAudience(NavigationAudience::Root);

    $item = NavigationItem::query()->where('route_name', 'test.reports')->firstOrFail();
    $item->update(['enabled' => false]);

    syncAudience(NavigationAudience::Root);

    expect($item->fresh()->enabled)->toBeTrue();
});

it('hides a catalog item whose route no longer exists', function () {
    $menu = NavigationMenu::factory()->create(['audience' => NavigationAudience::Root]);
    $group = NavigationGroup::factory()->catalog()->create(['navigation_menu_id' => $menu->id]);
    $item = NavigationItem::factory()->catalog()->create([
        'navigation_group_id' => $group->id,
        'route_name' => 'test.gone',
        'enabled' => true,
    ]);

    syncAudience(NavigationAudience::Root);

    expect($item->fresh()->enabled)->toBeFalse()
        ->and(NavigationItem::query()->whereKey($item->id)->exists())->toBeTrue();
});

it('never hides root-authored items', function () {
    $menu = NavigationMenu::factory()->create(['audience' => NavigationAudience::Root]);
    $group = NavigationGroup::factory()->catalog()->create(['navigation_menu_id' => $menu->id]);
    $item = NavigationItem::factory()->create([
        'navigation_group_id' => $group->id,
        'route_name' => 'test.gone',
        'source' => NavigationSource::Root,
        'enabled' => true,
    ]);

    syncAudience(NavigationAudience::Root);

    expect($item->fresh()->enabled)->toBeTrue();
});

it('does not duplicate a route already placed by root', function () {
    tagMenuRoute('test.reports', autopublishTag());

    $menu = NavigationMenu::factory()->create(['audience' => NavigationAudience::Root]);
    $group = NavigationGroup::factory()->create(['navigation_menu_id' => $menu->id]);
    NavigationItem::factory()->create([
        'navigation_group_id' => $group->id,
        'route_name' => 'test.reports',
        'source' => NavigationSource::Root,
    ]);

    syncAudience(NavigationAudience::Root);

    $items = NavigationItem::query()->where('route_name', 'test.reports')->get();

    expect($items)->toHaveCount(1)
        ->and($items->first()->source)->toBe(NavigationSource::Root);
});

it('restores an overridden catalog item to the catalog defaults', function () {
    tagMenuRoute('test.reports', autopublishTag());
    syncAudience(NavigationAudience::Root);

    $item = NavigationItem::query()->where('route_name', 'test.reports')->firstOrFail();
    $item->update(['overridden' => true, 'enabled' => false, 'position' => 99]);

    app(NavigationSync::class)->restoreItem($item);

    expect($item->fresh()->overridden)->toBeFalse()
        ->and($item->fresh()->enabled)->toBeTrue()
        ->and($item->fresh()->position)->not->toBe(99);
});

it('lists tagged routes that are not placed and places them', function () {
    tagMenuRoute('test.reports', [
        'label' => 'Reports',
        'group' => 'Analytics',
        'audiences' => ['root'],
    ]);

    $menu = NavigationMenu::factory()->create(['audience' => NavigationAudience::Root]);
    $sync = app(NavigationSync::class);

    expect(collect($sync->availableFor($menu))->pluck('route_name')->all())->toContain('test.reports');

    $item = $sync->place($menu, 'test.reports');

    expect($item)->not->toBeNull()
        ->and($item?->source)->toBe(NavigationSource::Catalog)
        ->and(collect($sync->availableFor($menu))->pluck('route_name')->all())->not->toContain('test.reports');
});

it('autopublishes every tagged route when the global switch is on', function () {
    config(['navigation.autopublish' => true]);

    tagMenuRoute('test.docs', [
        'label' => 'Docs',
        'group' => 'Docs',
        'audiences' => ['user'],
    ]);

    syncAudience(NavigationAudience::User);

    expect(NavigationItem::query()->where('route_name', 'test.docs')->exists())->toBeTrue();
});

it('honors an explicit auto false over the global switch', function () {
    config(['navigation.autopublish' => true]);

    tagMenuRoute('test.secret', [
        'label' => 'Secret',
        'audiences' => ['user'],
        'auto' => false,
    ]);

    syncAudience(NavigationAudience::User);

    expect(NavigationItem::query()->where('route_name', 'test.secret')->exists())->toBeFalse();
});

it('runs the sync command for one audience', function () {
    tagMenuRoute('test.reports', autopublishTag());

    $this->artisan('navigation:sync', ['--audience' => 'root'])->assertSuccessful();

    expect(NavigationItem::query()->where('route_name', 'test.reports')->exists())->toBeTrue();
});

it('rejects an unknown audience in the sync command', function () {
    $this->artisan('navigation:sync', ['--audience' => 'bogus'])->assertFailed();
});

it('keeps catalog items independent per audience', function () {
    tagMenuRoute('test.reports', autopublishTag(audiences: ['root', 'admin']));

    syncAudience(NavigationAudience::Root);
    syncAudience(NavigationAudience::Admin);

    $rootMenu = NavigationMenu::query()->where('audience', NavigationAudience::Root)->firstOrFail();
    $adminMenu = NavigationMenu::query()->where('audience', NavigationAudience::Admin)->firstOrFail();

    expect(NavigationItem::query()
        ->where('route_name', 'test.reports')
        ->whereHas('group', fn ($query) => $query->where('navigation_menu_id', $rootMenu->id))
        ->exists())->toBeTrue();

    expect(NavigationItem::query()
        ->where('route_name', 'test.reports')
        ->whereHas('group', fn ($query) => $query->where('navigation_menu_id', $adminMenu->id))
        ->exists())->toBeTrue();
});
