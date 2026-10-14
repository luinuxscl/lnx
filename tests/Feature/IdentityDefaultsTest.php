<?php

use App\Actions\ApplyIdentityDefaults;
use App\Models\Setting;

it('does not create a row when defaults match the kit fallbacks', function () {
    app(ApplyIdentityDefaults::class)->handle([
        'site_title' => config('app.name'),
        'locale' => config('app.locale'),
        'timezone' => config('app.timezone'),
    ]);

    expect(Setting::query()->count())->toBe(0);
});

it('fills undefined values', function () {
    app(ApplyIdentityDefaults::class)->handle(['site_title' => 'Acme']);

    expect(Setting::query()->first()->site_title)->toBe('Acme');
});

it('never overwrites an already defined value', function () {
    Setting::factory()->create(['site_title' => 'Root Title']);

    app(ApplyIdentityDefaults::class)->handle(['site_title' => 'Package Title']);

    expect(Setting::query()->first()->site_title)->toBe('Root Title');
});

it('ignores null and empty values', function () {
    app(ApplyIdentityDefaults::class)->handle([
        'site_title' => null,
        'site_tagline' => '',
    ]);

    expect(Setting::query()->count())->toBe(0);
});

it('rejects an unknown identity field', function () {
    expect(fn () => app(ApplyIdentityDefaults::class)->handle(['bogus' => 'x']))
        ->toThrow(InvalidArgumentException::class);
});
