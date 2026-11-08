<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Luinuxscl\LnxSam\SamBrief;
use Luinuxscl\LnxSam\SamBriefNarrativeAgent;
use Luinuxscl\LnxSam\SamRuntime;
use Luinuxscl\LnxSam\SamSignal;

beforeEach(function (): void {
    resetSamFixtures();
    Cache::flush();
});

function narrativeBrief(?array $signals = null): SamBrief
{
    return new SamBrief(
        source: 'fake-brief',
        resource: 'contact',
        id: '42',
        label: 'Ada Lovelace',
        resourceLabel: 'Contacto',
        signals: $signals ?? [
            new SamSignal('missing_company', true, 'https://example.test/contacts/42'),
            new SamSignal('duplicate_email', 2, 'https://example.test/contacts?q=ada@example.test'),
        ],
        narrative: 'Ada Lovelace no tiene empresa asignada.',
    );
}

it('returns a grounded model narrative', function (): void {
    configureSam();
    grantSamPermission();

    SamBriefNarrativeAgent::fake([
        'Ada Lovelace no tiene empresa asignada. Su correo aparece en 2 contactos más.',
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    expect(app(SamRuntime::class)->narrateBrief($user, narrativeBrief()))
        ->toBe('Ada Lovelace no tiene empresa asignada. Su correo aparece en 2 contactos más.');
});

it('rejects a narrative with a number outside the signals', function (): void {
    configureSam();
    grantSamPermission();

    SamBriefNarrativeAgent::fake([
        'Ada Lovelace no tiene empresa asignada. Su correo aparece en 3 contactos más.',
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    expect(app(SamRuntime::class)->narrateBrief($user, narrativeBrief()))->toBeNull();
});

it('rejects a narrative that claims a write', function (): void {
    configureSam();
    grantSamPermission();

    SamBriefNarrativeAgent::fake([
        'He actualizado la ficha de Ada Lovelace y ya está hecho.',
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    expect(app(SamRuntime::class)->narrateBrief($user, narrativeBrief()))->toBeNull();
});

it('reuses the cached narrative for the same brief and actor', function (): void {
    configureSam();
    grantSamPermission();

    SamBriefNarrativeAgent::fake([
        'Ada Lovelace no tiene empresa asignada. Su correo aparece en 2 contactos más.',
        'Otra narrativa distinta.',
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    $runtime = app(SamRuntime::class);

    $first = $runtime->narrateBrief($user, narrativeBrief());
    $second = $runtime->narrateBrief($user, narrativeBrief());

    expect($first)->toBe($second);
    SamBriefNarrativeAgent::assertPromptedTimes(1);
});

it('ignores a narrative that arrives after the timeout', function (): void {
    configureSam();
    grantSamPermission();
    config()->set('lnx_sam.brief_timeout', 1);

    SamBriefNarrativeAgent::fake(function (): string {
        usleep(1_200_000);

        return 'Ada Lovelace no tiene empresa asignada.';
    });

    $user = User::factory()->create();
    $this->actingAs($user);

    expect(app(SamRuntime::class)->narrateBrief($user, narrativeBrief()))->toBeNull();
});

it('answers null when the provider fails so the fallback stays', function (): void {
    configureSam();
    grantSamPermission();

    SamBriefNarrativeAgent::fake(
        fn () => throw InsufficientCreditsException::forProvider('openrouter'),
    );

    $user = User::factory()->create();
    $this->actingAs($user);

    expect(app(SamRuntime::class)->narrateBrief($user, narrativeBrief()))->toBeNull();
});

it('does not call the model when the brief has no signals', function (): void {
    configureSam();
    grantSamPermission();

    SamBriefNarrativeAgent::fake(['No veo puntos de atención.']);

    $user = User::factory()->create();
    $this->actingAs($user);

    expect(app(SamRuntime::class)->narrateBrief($user, narrativeBrief([])))->toBeNull();
    SamBriefNarrativeAgent::assertPromptedTimes(0);
});

it('denies the narrative without the surface permission', function (): void {
    configureSam();

    SamBriefNarrativeAgent::fake(['Ada Lovelace no tiene empresa asignada.']);

    $user = User::factory()->create();
    $this->actingAs($user);

    expect(app(SamRuntime::class)->narrateBrief($user, narrativeBrief()))->toBeNull();
    SamBriefNarrativeAgent::assertPromptedTimes(0);
});
