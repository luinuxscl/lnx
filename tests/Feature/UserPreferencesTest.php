<?php

use App\Actions\ListAdministrativeActivity;
use App\Enums\Role;
use App\Livewire\LocaleSwitcher;
use App\Livewire\Settings\Appearance;
use App\Models\AdministrativeActivity;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function preferencesUser(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

/*
|--------------------------------------------------------------------------
| Locale
|--------------------------------------------------------------------------
*/

it('recovers the account locale on a fresh session', function () {
    $user = preferencesUser(['locale' => 'en']);

    $this->actingAs($user)
        ->withSession([])
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Welcome');
});

it('prefers the account locale over an old session value', function () {
    $user = preferencesUser(['locale' => 'en']);

    $this->actingAs($user)
        ->withSession(['locale' => 'es'])
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Welcome');
});

it('keeps the session locale for guests', function () {
    $this->withSession(['locale' => 'en'])
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Welcome');
});

it('persists the locale on the account through the switcher', function () {
    $user = preferencesUser();

    Livewire::actingAs($user)
        ->test(LocaleSwitcher::class)
        ->call('setLocale', 'en')
        ->assertSet('locale', 'en');

    expect($user->refresh()->locale)->toBe('en');
});

it('persists the locale on the account through the controller', function () {
    $user = preferencesUser();

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect(route('home'));

    expect($user->refresh()->locale)->toBe('en');
});

/*
|--------------------------------------------------------------------------
| Appearance preferences
|--------------------------------------------------------------------------
*/

it('saves appearance preferences on the account', function () {
    $user = preferencesUser();

    Livewire::actingAs($user)
        ->test(Appearance::class)
        ->call('saveTheme', 'teal')
        ->call('saveColorScheme', 'dark')
        ->set('timezone', 'America/New_York')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->theme)->toBe('teal')
        ->and($user->color_scheme)->toBe('dark')
        ->and($user->timezone)->toBe('America/New_York');
});

it('rejects unsupported themes and color schemes', function () {
    $user = preferencesUser();
    $component = Livewire::actingAs($user)->test(Appearance::class);

    $component->call('saveTheme', 'neon')->assertHasErrors(['theme']);
    $component->call('saveColorScheme', 'sepia')->assertHasErrors(['color_scheme']);

    $user->refresh();

    expect($user->theme)->toBeNull()
        ->and($user->color_scheme)->toBeNull();
});

it('rejects an invalid time zone', function () {
    $user = preferencesUser();

    Livewire::actingAs($user)
        ->test(Appearance::class)
        ->set('timezone', 'Mars/Olympus')
        ->assertHasErrors(['timezone']);

    expect($user->refresh()->timezone)->toBeNull();
});

it('rejects an unsupported locale in the appearance form', function () {
    $user = preferencesUser(['locale' => 'es']);

    Livewire::actingAs($user)
        ->test(Appearance::class)
        ->set('locale', 'fr')
        ->assertHasErrors(['locale']);

    expect($user->refresh()->locale)->toBe('es');
});

/*
|--------------------------------------------------------------------------
| Browser hydration
|--------------------------------------------------------------------------
*/

it('seeds the browser appearance from the account for authenticated users', function () {
    $user = preferencesUser(['theme' => 'teal', 'color_scheme' => 'dark']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee("localStorage.setItem('flux.theme', \"teal\")", false)
        ->assertSee("localStorage.setItem('flux.appearance', \"dark\")", false);
});

it('does not seed the browser appearance for guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee("localStorage.setItem('flux.theme'", false)
        ->assertDontSee("localStorage.setItem('flux.appearance'", false);
});

/*
|--------------------------------------------------------------------------
| Time zone rendering and filters
|--------------------------------------------------------------------------
*/

it('renders dates in the account time zone', function () {
    $root = preferencesUser(['timezone' => 'America/New_York']);
    $root->assignRole(Role::Root->value);

    AdministrativeActivity::factory()->create([
        'occurred_at' => Carbon::parse('2026-01-01 23:30:00', 'UTC'),
    ]);

    $this->actingAs($root)
        ->get(route('admin.activity.index'))
        ->assertOk()
        ->assertSee('2026-01-01 18:30:00');
});

it('interprets day filters in the account time zone', function () {
    $root = preferencesUser(['timezone' => 'America/New_York']);
    $root->assignRole(Role::Root->value);

    $activity = AdministrativeActivity::factory()->create([
        'occurred_at' => Carbon::parse('2026-01-02 02:00:00', 'UTC'),
    ]);

    $this->actingAs($root);

    $results = app(ListAdministrativeActivity::class)->handle([
        'from' => '2026-01-01',
        'to' => '2026-01-01',
    ]);

    expect($results->pluck('id'))->toContain($activity->id);
});

it('keeps the application time zone for users without a preference', function () {
    $root = preferencesUser();
    $root->assignRole(Role::Root->value);

    AdministrativeActivity::factory()->create([
        'occurred_at' => Carbon::parse('2026-01-01 23:30:00', 'UTC'),
    ]);

    $this->actingAs($root)
        ->get(route('admin.activity.index'))
        ->assertOk()
        ->assertSee('2026-01-01 23:30:00');
});

/*
|--------------------------------------------------------------------------
| Authorization
|--------------------------------------------------------------------------
*/

it('requires authentication for the appearance settings', function () {
    $this->get(route('appearance.edit'))->assertRedirect(route('login'));
});
