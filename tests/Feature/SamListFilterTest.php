<?php

use App\Jobs\WarmSamFilterSignalsJob;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Luinuxscl\LnxSam\SamPreferences;
use Luinuxscl\LnxSam\SamRegistry;
use Luinuxscl\LnxSam\SamRuntime;
use Tests\Fixtures\Sam\FakeSamPreferences;
use Tests\Fixtures\Sam\ListFilterFakeSamContributor;

beforeEach(function (): void {
    ListFilterFakeSamContributor::$calls = 0;
    FakeSamPreferences::$preferences = null;
    resetSamFixtures();
    Cache::flush();
});

it('skips filter analysis when proactive alerts are off', function (): void {
    configureSam();
    grantSamPermission();

    $actor = User::factory()->create();

    app(SamRegistry::class)->register('fake', ListFilterFakeSamContributor::class);

    $this->actingAs($actor);

    $analysis = app(SamRuntime::class)->signalsForFilter($actor, 'fake', 'contact', ['search' => 'acme']);

    expect($analysis)->toBeNull()
        ->and(ListFilterFakeSamContributor::$calls)->toBe(0);
});

it('returns batched filter signals when proactive alerts are on', function (): void {
    configureSam();
    grantSamPermission();

    $actor = User::factory()->create();

    app(SamRegistry::class)->register('fake', ListFilterFakeSamContributor::class);
    app(SamRegistry::class)->registerPreferences('fake', FakeSamPreferences::class);
    FakeSamPreferences::$preferences = new SamPreferences(proactive: true);

    $this->actingAs($actor);

    $runtime = app(SamRuntime::class);

    $analysis = $runtime->signalsForFilter($actor, 'fake', 'contact', ['search' => 'acme']);

    expect($analysis)->not->toBeNull()
        ->and($analysis->signals)->toHaveCount(1)
        ->and($analysis->signals[0]->name)->toBe('missing_company')
        ->and(ListFilterFakeSamContributor::$calls)->toBe(1);

    $cached = $runtime->signalsForFilter($actor, 'fake', 'contact', ['search' => 'acme']);

    expect($cached?->signals[0]->name)->toBe('missing_company')
        ->and(ListFilterFakeSamContributor::$calls)->toBe(1);
});

it('queues filter warmup only when proactive alerts are on', function (): void {
    Queue::fake();
    configureSam();
    grantSamPermission();

    $actor = User::factory()->create();

    app(SamRegistry::class)->registerPreferences('fake', FakeSamPreferences::class);
    FakeSamPreferences::$preferences = new SamPreferences(proactive: true);

    $this->actingAs($actor);

    app(SamRuntime::class)->queueFilterSignalsWarmup($actor, 'fake', 'contact', ['search' => 'acme']);

    Queue::assertPushed(WarmSamFilterSignalsJob::class);
});
