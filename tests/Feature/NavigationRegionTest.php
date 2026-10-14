<?php

use App\Actions\NavigationSync;
use App\Enums\NavigationAudience;
use App\Enums\NavigationRegion;
use App\Enums\Role;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\User;
use App\Support\Navigation\MenuResolver;
use Database\Seeders\NavigationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    app()->setLocale('en');
    $this->seed(RolePermissionSeeder::class);
    $this->seed(NavigationSeeder::class);
});

function regionUser(?Role $role = null): User
{
    $user = User::factory()->create();

    if ($role !== null) {
        $user->assignRole($role->value);
    }

    return $user;
}

function regionMenu(NavigationAudience $audience): NavigationMenu
{
    return NavigationMenu::query()->where('audience', $audience)->firstOrFail();
}

it('splits the root sidebar into main and bottom regions', function () {
    $groups = collect(app(MenuResolver::class)->resolve(regionUser(Role::Root)));

    $main = $groups->where('region', NavigationRegion::Main->value);
    $bottom = $groups->where('region', NavigationRegion::Bottom->value);

    expect($main)->toHaveCount(1)
        ->and($main->first()['label'])->toBeNull()
        ->and(collect($main->first()['items'])->pluck('label'))->toContain('Dashboard');

    expect($bottom->pluck('label')->all())->toContain('Developer Mode');

    $developerItems = collect($bottom->first()['items'])->pluck('label');
    expect($developerItems)->toContain('Support')->toContain('Chat')->toContain('Installed packages');
});

it('gives admin a curated administration region and standard users none', function () {
    $admin = collect(app(MenuResolver::class)->resolve(regionUser(Role::Admin)));
    expect($admin->where('region', NavigationRegion::Bottom->value)->pluck('label')->all())
        ->toContain('Administration');

    $user = collect(app(MenuResolver::class)->resolve(regionUser()));
    expect($user->where('region', NavigationRegion::Bottom->value))->toHaveCount(0);
});

it('never autopublishes a route tagged for the bottom region', function () {
    config(['navigation.autopublish' => true]);

    Route::get('/testing/bottom-region', fn (): string => 'ok')->name('test.bottom-region')->menu([
        'label' => 'Bottom thing',
        'audiences' => ['root'],
        'region' => 'bottom',
    ]);
    Route::getRoutes()->refreshNameLookups();

    app(NavigationSync::class)->handle(NavigationAudience::Root);

    expect(NavigationItem::query()->where('route_name', 'test.bottom-region')->exists())->toBeFalse();
});

it('autopublishes main region routes into the main region', function () {
    Route::get('/testing/main-region', fn (): string => 'ok')->name('test.main-region')->menu([
        'label' => 'Main thing',
        'audiences' => ['root'],
        'region' => 'main',
        'auto' => true,
    ]);
    Route::getRoutes()->refreshNameLookups();

    app(NavigationSync::class)->handle(NavigationAudience::Root);

    $item = NavigationItem::query()->where('route_name', 'test.main-region')->firstOrFail();

    expect($item->group->region)->toBe(NavigationRegion::Main);
});

it('does not offer bottom region routes as available in main', function () {
    Route::get('/testing/bottom-available', fn (): string => 'ok')->name('test.bottom-available')->menu([
        'label' => 'Bottom available',
        'audiences' => ['root'],
        'region' => 'bottom',
    ]);
    Route::getRoutes()->refreshNameLookups();

    $available = collect(app(NavigationSync::class)->availableFor(regionMenu(NavigationAudience::Root)))
        ->pluck('route_name')
        ->all();

    expect($available)->not->toContain('test.bottom-available');
});

it('ensures the curated bottom region idempotently', function () {
    $menu = regionMenu(NavigationAudience::Root);

    expect($menu->groups()->where('region', NavigationRegion::Bottom)->count())->toBe(1);

    $this->seed(NavigationSeeder::class);

    expect($menu->fresh()->groups()->where('region', NavigationRegion::Bottom)->pluck('label_key')->all())
        ->toBe(['Developer Mode']);
});

it('uses a panel icon for the dashboard item', function () {
    $menu = regionMenu(NavigationAudience::Root);

    $item = NavigationItem::query()
        ->where('route_name', 'dashboard')
        ->whereHas('group', fn ($query) => $query->where('navigation_menu_id', $menu->id))
        ->firstOrFail();

    expect($item->menuIcon())->toBe('squares-2x2');
});
