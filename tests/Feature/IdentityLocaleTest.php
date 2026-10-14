<?php

use App\Models\Setting;

it('applies the site locale to guest requests', function () {
    Setting::factory()->create(['locale' => 'en']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Welcome')
        ->assertSee('Log in');
});

it('applies the site timezone to the application configuration', function () {
    Setting::factory()->create(['timezone' => 'Europe/Madrid']);

    $this->get(route('home'))->assertOk();

    expect(config('app.timezone'))->toBe('Europe/Madrid');
});

it('keeps the configured defaults when the settings row leaves them empty', function () {
    Setting::factory()->create(['locale' => null, 'timezone' => null]);

    $this->get(route('home'))->assertOk()->assertSee('Bienvenido');

    expect(config('app.locale'))->toBe('es')
        ->and(config('app.timezone'))->toBe('America/Santiago');
});
