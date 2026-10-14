<?php

use App\Support\Settings\IdentitySeederRegistry;
use Database\Seeders\IdentitySeeder;
use Tests\Fixtures\Seeders\AltIdentitySeeder;
use Tests\Fixtures\Seeders\DemoIdentitySeeder;

it('registers identity seeders and reports them', function () {
    $registry = new IdentitySeederRegistry;

    expect($registry->isEmpty())->toBeTrue();

    $registry->register('demo', DemoIdentitySeeder::class, 10);

    expect($registry->isEmpty())->toBeFalse()
        ->and($registry->all())->toBe([DemoIdentitySeeder::class]);
});

it('orders seeders by priority descending', function () {
    $registry = new IdentitySeederRegistry;

    $registry->register('low', AltIdentitySeeder::class, 1);
    $registry->register('high', DemoIdentitySeeder::class, 100);
    $registry->register('mid', IdentitySeeder::class, 50);

    expect($registry->all())->toBe([
        DemoIdentitySeeder::class,
        IdentitySeeder::class,
        AltIdentitySeeder::class,
    ]);
});

it('keeps the registration order for equal priorities', function () {
    $registry = new IdentitySeederRegistry;

    $registry->register('first', AltIdentitySeeder::class, 5);
    $registry->register('second', DemoIdentitySeeder::class, 5);

    expect($registry->all())->toBe([AltIdentitySeeder::class, DemoIdentitySeeder::class]);
});

it('replaces a previously registered source without duplicating it', function () {
    $registry = new IdentitySeederRegistry;

    $registry->register('demo', AltIdentitySeeder::class, 1);
    $registry->register('demo', DemoIdentitySeeder::class, 1);

    expect($registry->all())->toBe([DemoIdentitySeeder::class]);
});

it('rejects an empty source', function () {
    expect(fn () => (new IdentitySeederRegistry)->register('', DemoIdentitySeeder::class))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects a class that is not a seeder', function () {
    $registry = new IdentitySeederRegistry;

    expect(fn () => $registry->register('demo', stdClass::class))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $registry->register('demo', 'Does\\Not\\Exist'))
        ->toThrow(InvalidArgumentException::class);
});
