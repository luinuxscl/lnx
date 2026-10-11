<?php

use App\Support\Packages\PackageRegistry;

it('registers a package with sensible defaults', function () {
    $registry = new PackageRegistry;

    $registry->register('demo', [
        'name' => 'acme/demo',
        'label' => 'Acme Demo',
    ]);

    expect($registry->all())->toHaveCount(1);

    $package = $registry->all()[0];

    expect($package['source'])->toBe('demo');
    expect($package['name'])->toBe('acme/demo');
    expect($package['label'])->toBe('Acme Demo');
    expect($package['description'])->toBeNull();
    expect($package['repository'])->toBe('acme/demo');
    expect($package['url'])->toBeNull();
    expect($package['update_command'])->toBe('composer update acme/demo');
});

it('keeps overrides for repository, url and update command', function () {
    $registry = new PackageRegistry;

    $registry->register('demo', [
        'name' => 'acme/demo',
        'label' => 'Acme Demo',
        'description' => 'A demo module.',
        'repository' => 'acme/demo-module',
        'url' => 'https://example.com/demo',
        'update_command' => 'composer update acme/demo --with-dependencies',
    ]);

    $package = $registry->all()[0];

    expect($package['repository'])->toBe('acme/demo-module');
    expect($package['url'])->toBe('https://example.com/demo');
    expect($package['update_command'])->toBe('composer update acme/demo --with-dependencies');
    expect($package['description'])->toBe('A demo module.');
});

it('rejects an empty source', function () {
    expect(fn () => (new PackageRegistry)->register('', ['name' => 'acme/demo', 'label' => 'Acme']))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects a missing or malformed composer name', function () {
    $registry = new PackageRegistry;

    expect(fn () => $registry->register('demo', ['label' => 'Acme']))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $registry->register('demo', ['name' => 'acme', 'label' => 'Acme']))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects a missing label', function () {
    expect(fn () => (new PackageRegistry)->register('demo', ['name' => 'acme/demo', 'label' => '']))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects a non http url', function () {
    expect(fn () => (new PackageRegistry)->register('demo', [
        'name' => 'acme/demo',
        'label' => 'Acme',
        'url' => 'javascript:alert(1)',
    ]))->toThrow(InvalidArgumentException::class);
});
