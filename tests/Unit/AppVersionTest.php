<?php

use App\Support\AppVersion;

it('normalizes an exact tag to a clean version', function () {
    expect(AppVersion::normalize("v1.2.3-0-gf95ad6c\n"))->toBe('v1.2.3');
});

it('keeps the commit drift and dirty suffix in the describe output', function () {
    expect(AppVersion::normalize('v1.2.3-2-gf95ad6c'))->toBe('v1.2.3-2-gf95ad6c')
        ->and(AppVersion::normalize('v1.2.3-2-gf95ad6c-dirty'))->toBe('v1.2.3-2-gf95ad6c-dirty')
        ->and(AppVersion::normalize('v1.2.3-0-gf95ad6c-dirty'))->toBe('v1.2.3-dirty');
});

it('keeps a bare commit hash when the repository has no tags', function () {
    expect(AppVersion::normalize('f95ad6c'))->toBe('f95ad6c')
        ->and(AppVersion::normalize('f95ad6c-dirty'))->toBe('f95ad6c-dirty');
});
