<?php

use App\Ai\Sam\SamRegistry;
use App\Ai\Sam\SamRuntime;
use App\Models\User;
use Tests\Fixtures\Sam\AmbientFakeSamContributor;

beforeEach(function (): void {
    AmbientFakeSamContributor::$lastIds = [];
    resetSamFixtures();
});

it('returns batched ambient signals from contributors that declare them', function (): void {
    configureSam();
    grantSamPermission();

    $actor = User::factory()->create();

    app(SamRegistry::class)->register('fake', AmbientFakeSamContributor::class);

    $this->actingAs($actor);

    $rows = app(SamRuntime::class)->ambientSignalsForRows($actor, 'contact', ['42']);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->signals[0]->proactive)->toBeTrue();
});

it('caps ambient row ids at one hundred', function (): void {
    configureSam();
    grantSamPermission();

    $actor = User::factory()->create();

    app(SamRegistry::class)->register('fake', AmbientFakeSamContributor::class);

    $this->actingAs($actor);

    app(SamRuntime::class)->ambientSignalsForRows($actor, 'contact', array_map('strval', range(1, 150)));

    expect(AmbientFakeSamContributor::$lastIds)->toHaveCount(100);
});
