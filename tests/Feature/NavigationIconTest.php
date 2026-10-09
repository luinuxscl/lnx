<?php

use App\Enums\NavigationAudience;
use App\Enums\Role;
use App\Livewire\Navigation\Index;
use App\Models\NavigationGroup;
use App\Models\NavigationMenu;
use App\Models\User;
use App\Support\Navigation\MenuResolver;
use Database\Seeders\NavigationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function () {
    app()->setLocale('en');
    $this->seed(RolePermissionSeeder::class);
    $this->seed(NavigationSeeder::class);
});

function iconRootUser(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Root->value);

    return $user;
}

function iconRootMenu(): NavigationMenu
{
    return NavigationMenu::query()->where('audience', NavigationAudience::Root)->firstOrFail();
}

it('rejects an unknown group icon', function () {
    Livewire::actingAs(iconRootUser())->test(Index::class)
        ->call('openGroupCreator')
        ->set('groupLabelEs', 'Grupo')
        ->set('groupLabelEn', 'Group')
        ->set('groupIcon', 'definitely-not-an-icon')
        ->call('saveGroup')
        ->assertHasErrors('groupIcon');

    expect(NavigationGroup::query()->where('label_en', 'Group')->exists())->toBeFalse();
});

it('accepts a Heroicon and an imported Lucide icon', function () {
    foreach (['cog-6-tooth', 'boxes'] as $icon) {
        Livewire::actingAs(iconRootUser())->test(Index::class)
            ->call('openGroupCreator')
            ->set('groupLabelEs', 'Grupo')
            ->set('groupLabelEn', 'Group')
            ->set('groupIcon', $icon)
            ->call('saveGroup')
            ->assertHasNoErrors();
    }

    expect(NavigationGroup::query()->where('icon', 'boxes')->exists())->toBeTrue();
});

it('rejects an unknown item icon', function () {
    $group = iconRootMenu()->groups()->firstOrFail();

    Livewire::actingAs(iconRootUser())->test(Index::class)
        ->call('openItemCreator', $group->id)
        ->set('itemType', 'route')
        ->set('itemRoute', 'admin.users.index')
        ->set('itemIcon', 'definitely-not-an-icon')
        ->call('saveItem')
        ->assertHasErrors('itemIcon');
});

it('renders the manager when stored icons are unknown', function () {
    $group = iconRootMenu()->groups()->firstOrFail();
    $group->update(['icon' => 'definitely-not-an-icon']);
    $group->items()->firstOrFail()->update(['icon' => 'definitely-not-an-icon']);

    $this->actingAs(iconRootUser())
        ->get(route('admin.navigation.index'))
        ->assertOk();
});

it('falls back to a safe icon for an unknown route tag icon', function () {
    Route::get('/testing/bad-icon', fn (): string => 'ok')->name('test.bad-icon')->menu([
        'label' => 'Bad Icon',
        'icon' => 'definitely-not-an-icon',
        'audiences' => ['root'],
    ]);

    Route::getRoutes()->refreshNameLookups();

    NavigationMenu::query()->delete();

    $groups = app(MenuResolver::class)->resolve(iconRootUser());

    $item = collect($groups)
        ->flatMap(fn (array $group) => $group['items'])
        ->first(fn (array $item): bool => $item['label'] === 'Bad Icon');

    expect($item)->not->toBeNull();
    expect($item['icon'])->toBe('link');
});
