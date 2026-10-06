<?php

use App\Models\Setting;
use App\Models\User;

it('exposes the kit defaults when no settings row exists', function () {
    $settings = Setting::current();

    expect($settings->title())->toBe('LNX')
        ->and($settings->tagline())->toBe('Base de desarrollo Laravel')
        ->and($settings->footer())->toBe('Hecho por Like Innovación')
        ->and($settings->logoUrl())->toContain(Setting::DEFAULT_LOGO)
        ->and($settings->faviconUrl())->toContain(Setting::DEFAULT_FAVICON)
        ->and($settings->appleTouchIconUrl())->toContain(Setting::DEFAULT_APPLE_TOUCH_ICON);

    expect(config('app.timezone'))->toBe('America/Santiago');
    expect(config('app.locale'))->toBe('es');
});

it('renders the kit identity on the dashboard by default', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('LNX')
        ->assertSee('Base de desarrollo Laravel')
        ->assertSee('Hecho por Like Innovación')
        ->assertSee(asset(Setting::DEFAULT_FAVICON), false);
});

it('lets configured settings override the kit defaults', function () {
    Setting::factory()->create([
        'site_title' => 'Acme Portal',
        'site_tagline' => 'Custom tagline',
        'footer_text' => 'Custom footer',
    ]);

    expect(Setting::current()->title())->toBe('Acme Portal')
        ->and(Setting::current()->tagline())->toBe('Custom tagline')
        ->and(Setting::current()->footer())->toBe('Custom footer');
});
