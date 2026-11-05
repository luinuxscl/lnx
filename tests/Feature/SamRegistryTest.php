<?php

use App\Ai\Sam\SamRegistry;
use Tests\Fixtures\Sam\FakeSamContributor;
use Tests\Fixtures\Sam\FakeSamPanel;
use Tests\Fixtures\Sam\FakeSamProfile;
use Tests\Fixtures\Sam\MismatchedSamContributor;
use Tests\Fixtures\Sam\SecondFakeSamContributor;

beforeEach(function (): void {
    resetSamFixtures();
});

it('registers a contributor whose source matches', function (): void {
    app(SamRegistry::class)->register('fake', FakeSamContributor::class);

    $registry = app(SamRegistry::class);

    expect($registry->has('fake'))->toBeTrue()
        ->and($registry->contributor('fake'))->toBeInstanceOf(FakeSamContributor::class)
        ->and($registry->contributors())->toHaveCount(1);
});

it('rejects a source that does not match the contributor source', function (): void {
    app(SamRegistry::class)->register('fake', MismatchedSamContributor::class);
})->throws(InvalidArgumentException::class);

it('rejects an empty source', function (): void {
    app(SamRegistry::class)->register('  ', FakeSamContributor::class);
})->throws(InvalidArgumentException::class);

it('rejects a class that does not implement the contributor contract', function (): void {
    app(SamRegistry::class)->register('fake', stdClass::class);
})->throws(InvalidArgumentException::class);

it('is idempotent and the last declaration wins', function (): void {
    $registry = app(SamRegistry::class);

    $registry->register('fake', FakeSamContributor::class);
    $registry->register('fake', SecondFakeSamContributor::class);

    expect($registry->contributors())->toHaveCount(1)
        ->and($registry->contributor('fake'))->toBeInstanceOf(SecondFakeSamContributor::class);
});

it('registers the identity profile of the agent', function (): void {
    app(SamRegistry::class)->registerProfile('fake', FakeSamProfile::class);

    expect(app(SamRegistry::class)->profile())->toBeInstanceOf(FakeSamProfile::class)
        ->and(app(SamRegistry::class)->profile()->name())->toBe('Fake Sam');
});

it('rejects a profile that does not implement the contract', function (): void {
    app(SamRegistry::class)->registerProfile('fake', stdClass::class);
})->throws(InvalidArgumentException::class);

it('registers the Livewire surface of the agent', function (): void {
    app(SamRegistry::class)->registerSurface('fake', FakeSamPanel::class);

    expect(app(SamRegistry::class)->surface())->toBe(FakeSamPanel::class);
});

it('rejects a surface that is not a Livewire component', function (): void {
    app(SamRegistry::class)->registerSurface('fake', stdClass::class);
})->throws(InvalidArgumentException::class);

it('flushes every declaration', function (): void {
    $registry = app(SamRegistry::class);

    $registry->register('fake', FakeSamContributor::class);
    $registry->registerProfile('fake', FakeSamProfile::class);
    $registry->registerSurface('fake', FakeSamPanel::class);

    $registry->flush();

    expect($registry->contributors())->toBe([])
        ->and($registry->profile())->toBeNull()
        ->and($registry->surface())->toBeNull();
});
