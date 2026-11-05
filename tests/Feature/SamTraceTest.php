<?php

use App\Ai\Sam\SamRegistry;
use App\Ai\Sam\SamRuntime;
use App\Ai\Sam\SamTraceEntry;
use App\Ai\Sam\SamTurnTrace;
use App\Models\User;
use Tests\Fixtures\Sam\FakeSamTraceContributor;

beforeEach(function (): void {
    resetSamFixtures();

    FakeSamTraceContributor::$trace = null;
    FakeSamTraceContributor::$denies = false;
});

function registerFakeTrace(): void
{
    app(SamRegistry::class)->register('fake-trace', FakeSamTraceContributor::class);
}

it('answers the trace the module declares for the turn', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeTrace();

    FakeSamTraceContributor::$trace = new SamTurnTrace(
        source: 'fake-trace',
        correlationId: '11111111-1111-1111-1111-111111111111',
        entries: [
            new SamTraceEntry(
                tool: 'fake_read',
                label: 'Ada Lovelace',
                kind: SamTraceEntry::KIND_READ,
                status: 'read',
                resource: 'contact',
                count: 1,
                url: 'https://example.test/contacts/1',
            ),
        ],
        activityUrl: 'https://example.test/activity?turn=11111111-1111-1111-1111-111111111111',
    );

    $trace = app(SamRuntime::class)->traceFor(
        User::factory()->create(),
        '11111111-1111-1111-1111-111111111111',
    );

    expect($trace)->not->toBeNull()
        ->and($trace?->count())->toBe(1)
        ->and($trace?->entries[0]->label)->toBe('Ada Lovelace')
        ->and($trace?->activityUrl)->toContain('turn=11111111');
});

it('answers no trace for an empty correlation or an unknown turn', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeTrace();

    $user = User::factory()->create();

    expect(app(SamRuntime::class)->traceFor($user, null))->toBeNull()
        ->and(app(SamRuntime::class)->traceFor($user, '  '))->toBeNull()
        ->and(app(SamRuntime::class)->traceFor($user, '22222222-2222-2222-2222-222222222222'))->toBeNull();
});

it('answers no trace when the module cannot present it', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeTrace();

    FakeSamTraceContributor::$denies = true;

    expect(app(SamRuntime::class)->traceFor(
        User::factory()->create(),
        '11111111-1111-1111-1111-111111111111',
    ))->toBeNull();
});

it('denies the trace without the surface permission', function (): void {
    configureSam();
    registerFakeTrace();

    FakeSamTraceContributor::$trace = new SamTurnTrace(
        source: 'fake-trace',
        correlationId: '11111111-1111-1111-1111-111111111111',
    );

    expect(app(SamRuntime::class)->traceFor(
        User::factory()->create(),
        '11111111-1111-1111-1111-111111111111',
    ))->toBeNull();
});
