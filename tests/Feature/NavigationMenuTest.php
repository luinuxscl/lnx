<?php

use App\Enums\NavigationAudience;
use App\Enums\NavigationRegion;
use App\Enums\Role;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\User;
use App\Support\Navigation\MenuResolver;
use Database\Seeders\NavigationSeeder;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    app()->setLocale('en');
    $this->seed(RolePermissionSeeder::class);
    $this->seed(NavigationSeeder::class);
});

function navUser(?Role $role = null): User
{
    $user = User::factory()->create();

    if ($role !== null) {
        $user->assignRole($role->value);
    }

    return $user;
}

/**
 * @return array<int, string>
 */
function menuLabels(array $groups): array
{
    return collect($groups)->flatMap(fn (array $group) => collect($group['items'])->pluck('label'))->all();
}

it('seeds a menu for every audience', function () {
    expect(NavigationMenu::query()->pluck('audience')->all())
        ->toHaveCount(3);

    expect(NavigationMenu::query()->firstOrCreate(['audience' => NavigationAudience::User])->wasRecentlyCreated)->toBeFalse();
});

it('does not overwrite the existing main menu when seeded again', function () {
    $root = NavigationMenu::query()->where('audience', NavigationAudience::Root)->firstOrFail();
    $root->groups()->where('region', NavigationRegion::Main)->delete();

    $this->seed(NavigationSeeder::class);

    expect($root->fresh()->groups()->where('region', NavigationRegion::Main)->count())->toBe(0);
});

it('resolves the root menu with developer mode and kit ai tools', function () {
    $groups = app(MenuResolver::class)->resolve(navUser(Role::Root));
    $labels = menuLabels($groups);

    expect($labels)->toContain('Dashboard');
    expect($labels)->toContain('Support');
    expect($labels)->toContain('Users');
    expect($labels)->toContain('UI/UX components');
    expect($labels)->toContain('Chat');
    expect(collect($groups)->pluck('label')->all())->toContain('Developer Mode');
});

it('hides root-only tools from admin and standard users', function () {
    $adminLabels = menuLabels(app(MenuResolver::class)->resolve(navUser(Role::Admin)));
    expect($adminLabels)->toContain('Users');
    expect($adminLabels)->not->toContain('Support');
    expect($adminLabels)->not->toContain('Settings');
    expect($adminLabels)->not->toContain('Chat');

    $userLabels = menuLabels(app(MenuResolver::class)->resolve(navUser()));
    expect($userLabels)->toContain('Dashboard');
    expect($userLabels)->not->toContain('Chat');
    expect($userLabels)->not->toContain('Users');
    expect($userLabels)->not->toContain('Administration');
});

it('lets the highest role win for multi-role users', function () {
    $user = navUser(Role::Admin);
    $user->assignRole(Role::Root->value);

    expect(NavigationAudience::forUser($user))->toBe(NavigationAudience::Root);
});

it('skips disabled groups and items', function () {
    $root = NavigationMenu::query()->where('audience', NavigationAudience::Root)->firstOrFail();
    $group = $root->groups()->firstOrFail();

    $group->update(['enabled' => false]);

    $labels = menuLabels(app(MenuResolver::class)->resolve(navUser(Role::Root)));

    expect($labels)->not->toContain('Dashboard');
});

it('skips items whose route no longer exists', function () {
    $root = NavigationMenu::query()->where('audience', NavigationAudience::Root)->firstOrFail();
    $group = $root->groups()->skip(1)->firstOrFail();

    NavigationItem::query()->where('navigation_group_id', $group->id)->update(['route_name' => 'does.not.exist']);

    $labels = menuLabels(app(MenuResolver::class)->resolve(navUser(Role::Root)));

    expect($labels)->not->toContain('Support');
});

it('falls back to route tags when no menu row exists', function () {
    NavigationMenu::query()->delete();

    $labels = menuLabels(app(MenuResolver::class)->resolve(navUser(Role::Root)));

    expect($labels)->toContain('Dashboard');
    expect($labels)->toContain('Support');
    expect($labels)->not->toContain('Chat');
});

it('invalidates the cached menu after a change', function () {
    $resolver = app(MenuResolver::class);
    $before = menuLabels($resolver->resolve(navUser(Role::Root)));

    expect($before)->toContain('Dashboard');

    $root = NavigationMenu::query()->where('audience', NavigationAudience::Root)->firstOrFail();
    $root->groups()->firstOrFail()->update(['enabled' => false]);

    $after = menuLabels($resolver->resolve(navUser(Role::Root)));

    expect($after)->not->toContain('Dashboard');
});
