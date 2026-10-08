<?php

use App\Enums\AdministrativeAction;
use App\Enums\NavigationAudience;
use App\Enums\NavigationItemType;
use App\Enums\Role;
use App\Livewire\Navigation\Index;
use App\Models\AdministrativeActivity;
use App\Models\NavigationGroup;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\User;
use Database\Seeders\NavigationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    app()->setLocale('en');
    $this->seed(RolePermissionSeeder::class);
    $this->seed(NavigationSeeder::class);
});

function navigationRootUser(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Root->value);

    return $user;
}

function adminAudienceMenu(): NavigationMenu
{
    return NavigationMenu::query()->where('audience', NavigationAudience::Root)->firstOrFail();
}

it('redirects guests and forbids non-root users', function () {
    $this->get(route('admin.navigation.index'))->assertRedirect(route('login'));

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.navigation.index'))->assertForbidden();
});

it('renders for root', function () {
    $this->actingAs(navigationRootUser())->get(route('admin.navigation.index'))->assertOk()->assertSee('Navigation');
});

it('creates a group requiring both languages', function () {
    Livewire::actingAs(navigationRootUser())->test(Index::class)
        ->call('openGroupCreator')
        ->set('groupLabelEs', 'Reportes')
        ->set('groupLabelEn', '')
        ->call('saveGroup')
        ->assertHasErrors('groupLabelEn');

    Livewire::actingAs(navigationRootUser())->test(Index::class)
        ->call('openGroupCreator')
        ->set('groupLabelEs', 'Reportes')
        ->set('groupLabelEn', 'Reports')
        ->set('groupIcon', 'chart-bar')
        ->call('saveGroup')
        ->assertHasNoErrors();

    $group = adminAudienceMenu()->groups()->where('label_en', 'Reports')->firstOrFail();
    expect($group->label_es)->toBe('Reportes');
    expect($group->label())->toBe('Reports');

    expect(AdministrativeActivity::query()->where('action', AdministrativeAction::NavigationGroupCreated)->exists())->toBeTrue();
});

it('adds a tagged route item without labels', function () {
    $group = adminAudienceMenu()->groups()->firstOrFail();

    Livewire::actingAs(navigationRootUser())->test(Index::class)
        ->call('openItemCreator', $group->id)
        ->set('itemType', 'route')
        ->set('itemRoute', 'admin.users.index')
        ->call('saveItem')
        ->assertHasNoErrors();

    expect(NavigationItem::query()->where('route_name', 'admin.users.index')->where('navigation_group_id', $group->id)->exists())->toBeTrue();
});

it('requires labels for an untagged route', function () {
    $group = adminAudienceMenu()->groups()->firstOrFail();

    Livewire::actingAs(navigationRootUser())->test(Index::class)
        ->call('openItemCreator', $group->id)
        ->set('itemType', 'route')
        ->set('itemRoute', 'profile.edit')
        ->call('saveItem')
        ->assertHasErrors('itemLabelEs');

    Livewire::actingAs(navigationRootUser())->test(Index::class)
        ->call('openItemCreator', $group->id)
        ->set('itemType', 'route')
        ->set('itemRoute', 'profile.edit')
        ->set('itemLabelEs', 'Mi perfil')
        ->set('itemLabelEn', 'My profile')
        ->call('saveItem')
        ->assertHasNoErrors();

    expect(NavigationItem::query()->where('route_name', 'profile.edit')->exists())->toBeTrue();
});

it('rejects unsafe link destinations and accepts safe ones', function () {
    $group = adminAudienceMenu()->groups()->firstOrFail();

    foreach (['javascript:alert(1)', '//evil.example.com', 'data:text/html,x'] as $unsafe) {
        Livewire::actingAs(navigationRootUser())->test(Index::class)
            ->call('openItemCreator', $group->id)
            ->set('itemType', 'link')
            ->set('itemUrl', $unsafe)
            ->set('itemLabelEs', 'Promo')
            ->set('itemLabelEn', 'Promo')
            ->call('saveItem')
            ->assertHasErrors('itemUrl');
    }

    foreach (['https://example.com/landing', '/internal/path'] as $safe) {
        Livewire::actingAs(navigationRootUser())->test(Index::class)
            ->call('openItemCreator', $group->id)
            ->set('itemType', 'link')
            ->set('itemUrl', $safe)
            ->set('itemLabelEs', 'Promo')
            ->set('itemLabelEn', 'Promo')
            ->call('saveItem')
            ->assertHasNoErrors();
    }

    expect($group->items()->where('type', NavigationItemType::Link->value)->count())->toBe(2);
});

it('toggles item visibility', function () {
    $item = adminAudienceMenu()->groups()->firstOrFail()->items()->firstOrFail();

    Livewire::actingAs(navigationRootUser())->test(Index::class)
        ->call('toggleItem', $item->id)
        ->assertHasNoErrors();

    expect($item->fresh()->enabled)->toBeFalse();
});

it('reorders groups', function () {
    $groups = adminAudienceMenu()->groups()->pluck('id')->all();
    $last = end($groups);

    Livewire::actingAs(navigationRootUser())->test(Index::class)
        ->call('handleGroupSort', $last, 0)
        ->assertHasNoErrors();

    expect(NavigationGroup::query()->whereKey($last)->firstOrFail()->position)->toBe(0);
});

it('deletes an item', function () {
    $item = adminAudienceMenu()->groups()->firstOrFail()->items()->firstOrFail();

    Livewire::actingAs(navigationRootUser())->test(Index::class)
        ->call('deleteItem', $item->id)
        ->assertHasNoErrors();

    expect(NavigationItem::query()->whereKey($item->id)->exists())->toBeFalse();
    expect(AdministrativeActivity::query()->where('action', AdministrativeAction::NavigationItemDeleted)->exists())->toBeTrue();
});
