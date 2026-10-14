<?php

use App\Enums\Role;
use App\Livewire\Settings\General;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function homeSettingsRoot(): User
{
    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    return $root;
}

it('renders the landing page for guests', function () {
    Setting::factory()->create([
        'site_title' => 'Acme',
        'site_tagline' => 'Build faster',
        'locale' => 'en',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Acme')
        ->assertSee('Build faster')
        ->assertSee('Log in')
        ->assertSee('Register');
});

it('no longer shows the default Laravel template', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Laracasts')
        ->assertDontSee('Deploy now')
        ->assertDontSee("Let's get started");
});

it('shows the technology logos in the landing', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(__('Built with'))
        ->assertSee('Livewire')
        ->assertSee('Tailwind CSS')
        ->assertSee('PostgreSQL');
});

it('hides the register link when registration is invitation only', function () {
    Setting::factory()->create([
        'registration_mode' => 'invitation_only',
        'locale' => 'en',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Log in')
        ->assertDontSee('Register');
});

it('is enabled by default when no settings row exists', function () {
    expect(Setting::homeIsEnabled())->toBeTrue();

    $this->get(route('home'))->assertOk();
});

it('redirects guests to login when the home page is disabled', function () {
    Setting::factory()->create(['home_enabled' => false]);

    $this->get(route('home'))->assertRedirect(route('login'));
});

it('redirects authenticated users to the dashboard when the home page is disabled', function () {
    Setting::factory()->create(['home_enabled' => false]);

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});

it('lets root toggle the home page from the general settings', function () {
    $this->seed(RolePermissionSeeder::class);

    Livewire::actingAs(homeSettingsRoot())
        ->test(General::class)
        ->set('site_title', 'Acme')
        ->set('home_enabled', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::current()->home_enabled)->toBeFalse()
        ->and(Setting::homeIsEnabled())->toBeFalse();
});
