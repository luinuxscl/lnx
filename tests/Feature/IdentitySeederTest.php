<?php

use App\Actions\SeedIdentity;
use App\Models\Setting;
use App\Support\Settings\IdentitySeederRegistry;
use Tests\Fixtures\Seeders\AltIdentitySeeder;
use Tests\Fixtures\Seeders\DemoIdentitySeeder;

beforeEach(function () {
    DemoIdentitySeeder::$defaults = [
        'site_title' => 'Luz',
        'site_tagline' => 'Historial médico familiar',
        'footer_text' => 'Hecho por Luz',
        'locale' => 'en',
        'timezone' => 'Europe/Madrid',
    ];

    AltIdentitySeeder::$defaults = ['site_title' => 'Acme'];
});

it('leaves the identity untouched and creates no row when no package is installed', function () {
    app(SeedIdentity::class)->handle();

    expect(Setting::query()->count())->toBe(0)
        ->and(Setting::current()->title())->toBe('LNX');
});

it('seeds the identity contributed by a package', function () {
    app(IdentitySeederRegistry::class)->register('luz', DemoIdentitySeeder::class, 10);

    app(SeedIdentity::class)->handle();

    $settings = Setting::query()->first();

    expect($settings)->not->toBeNull()
        ->and($settings->site_title)->toBe('Luz')
        ->and($settings->site_tagline)->toBe('Historial médico familiar')
        ->and($settings->footer_text)->toBe('Hecho por Luz')
        ->and($settings->locale)->toBe('en')
        ->and($settings->timezone)->toBe('Europe/Madrid');
});

it('lets the higher priority package win at equal fields', function () {
    app(IdentitySeederRegistry::class)->register('luz', DemoIdentitySeeder::class, 100);
    app(IdentitySeederRegistry::class)->register('acme', AltIdentitySeeder::class, 10);

    app(SeedIdentity::class)->handle();

    expect(Setting::query()->first()->site_title)->toBe('Luz');
});

it('never overwrites what root saved', function () {
    Setting::factory()->create(['site_title' => 'Acme Portal', 'site_tagline' => null]);

    app(IdentitySeederRegistry::class)->register('luz', DemoIdentitySeeder::class, 10);

    app(SeedIdentity::class)->handle();

    $settings = Setting::query()->first();

    expect($settings->site_title)->toBe('Acme Portal')
        ->and($settings->site_tagline)->toBe('Historial médico familiar');
});

it('is idempotent when run twice', function () {
    app(IdentitySeederRegistry::class)->register('luz', DemoIdentitySeeder::class, 10);

    app(SeedIdentity::class)->handle();
    app(SeedIdentity::class)->handle();

    expect(Setting::query()->count())->toBe(1)
        ->and(Setting::query()->first()->site_title)->toBe('Luz');
});
