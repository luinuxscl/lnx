<?php

use App\Enums\Role;
use App\Livewire\Settings\General;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function rootUser(): User
{
    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    return $root;
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
});

test('users without the root role are forbidden', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)->get(route('admin.settings.edit'))->assertForbidden();
});

test('root users can view the general settings page', function () {
    $this->actingAs(rootUser())
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('Configuración general');
});

test('root users can update the settings', function () {
    Livewire::actingAs(rootUser())
        ->test(General::class)
        ->set('site_title', 'My Site')
        ->set('site_tagline', 'My tagline')
        ->set('meta_description', 'A description')
        ->set('admin_email', 'admin@example.com')
        ->set('footer_text', '© 2026 My Site')
        ->set('analytics_id', 'G-ABC123')
        ->set('maintenance_mode', true)
        ->set('maintenance_message', 'Back soon')
        ->call('save')
        ->assertHasNoErrors();

    $settings = Setting::first();

    expect($settings)->not->toBeNull()
        ->and($settings->site_title)->toBe('My Site')
        ->and($settings->site_tagline)->toBe('My tagline')
        ->and($settings->meta_description)->toBe('A description')
        ->and($settings->admin_email)->toBe('admin@example.com')
        ->and($settings->footer_text)->toBe('© 2026 My Site')
        ->and($settings->analytics_id)->toBe('G-ABC123')
        ->and($settings->maintenance_mode)->toBeTrue()
        ->and($settings->maintenance_message)->toBe('Back soon');
});

test('the site title is required', function () {
    Livewire::actingAs(rootUser())
        ->test(General::class)
        ->set('site_title', '')
        ->call('save')
        ->assertHasErrors(['site_title']);
});

test('the administration email must be a valid email address', function () {
    Livewire::actingAs(rootUser())
        ->test(General::class)
        ->set('site_title', 'My Site')
        ->set('admin_email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['admin_email']);
});

test('uploaded branding files are stored and can be removed', function () {
    Storage::fake('public');

    Livewire::actingAs(rootUser())
        ->test(General::class)
        ->set('site_title', 'My Site')
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->set('favicon', UploadedFile::fake()->image('favicon.png'))
        ->call('save')
        ->assertHasNoErrors();

    $settings = Setting::first();

    expect($settings->logo_path)->not->toBeNull()
        ->and($settings->favicon_path)->not->toBeNull();

    Storage::disk('public')->assertExists($settings->logo_path);
    Storage::disk('public')->assertExists($settings->favicon_path);

    Livewire::actingAs(rootUser())
        ->test(General::class)
        ->call('removeFile', 'logo');

    $settings->refresh();

    expect($settings->logo_path)->toBeNull();
});

test('the cached settings are refreshed after saving', function () {
    $root = rootUser();

    Livewire::actingAs($root)
        ->test(General::class)
        ->set('site_title', 'First')
        ->call('save');

    expect(Setting::current()->site_title)->toBe('First');

    Livewire::actingAs($root)
        ->test(General::class)
        ->set('site_title', 'Second')
        ->call('save');

    expect(Setting::current()->site_title)->toBe('Second');
});

test('the site title is applied to the application name', function () {
    Setting::factory()->create(['site_title' => 'Configured Title']);

    $this->actingAs(rootUser())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Configured Title');

    expect(config('app.name'))->toBe('Configured Title');
});

test('the administration email does not override the configured mail sender', function () {
    config(['mail.from.address' => 'noreply@luinuxscl.com']);
    Setting::factory()->create([
        'site_title' => 'Configured Title',
        'admin_email' => 'owner@example.com',
    ]);

    $this->actingAs(rootUser())->get(route('dashboard'))->assertOk();

    expect(config('mail.from.address'))->toBe('noreply@luinuxscl.com');
});

test('root can save the site locale and timezone', function () {
    Livewire::actingAs(rootUser())
        ->test(General::class)
        ->set('site_title', 'My Site')
        ->set('locale', 'en')
        ->set('timezone', 'Europe/Madrid')
        ->call('save')
        ->assertHasNoErrors();

    $settings = Setting::first();

    expect($settings->locale)->toBe('en')
        ->and($settings->timezone)->toBe('Europe/Madrid');
});

test('an unsupported locale is rejected', function () {
    Livewire::actingAs(rootUser())
        ->test(General::class)
        ->set('site_title', 'My Site')
        ->set('locale', 'fr')
        ->call('save')
        ->assertHasErrors(['locale']);
});

test('an invalid timezone is rejected', function () {
    Livewire::actingAs(rootUser())
        ->test(General::class)
        ->set('site_title', 'My Site')
        ->set('timezone', 'Not/AZone')
        ->call('save')
        ->assertHasErrors(['timezone']);
});

test('root can save the site accent theme', function () {
    Livewire::actingAs(rootUser())
        ->test(General::class)
        ->set('site_title', 'My Site')
        ->set('theme', 'violet')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::first()->theme)->toBe('violet')
        ->and(Setting::current()->accentTheme())->toBe('violet');
});

test('an unsupported site theme is rejected', function () {
    Livewire::actingAs(rootUser())
        ->test(General::class)
        ->set('site_title', 'My Site')
        ->set('theme', 'neon')
        ->call('save')
        ->assertHasErrors(['theme']);
});
