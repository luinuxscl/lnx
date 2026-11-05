<?php

use App\Ai\Sam\SamAction;
use App\Ai\Sam\SamBrief;
use App\Ai\Sam\SamContext;
use App\Ai\Sam\SamRegistry;
use App\Ai\Sam\SamRuntime;
use App\Models\User;
use Tests\Fixtures\Sam\FakeSamBriefContributor;

beforeEach(function (): void {
    resetSamFixtures();

    FakeSamBriefContributor::$brief = null;
    FakeSamBriefContributor::$insight = null;
});

function registerFakeBrief(): void
{
    app(SamRegistry::class)->register('fake-brief', FakeSamBriefContributor::class);
}

it('answers the brief the module composes for the record in focus', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeBrief();

    FakeSamBriefContributor::$brief = new SamBrief(
        source: 'fake-brief',
        resource: 'contact',
        id: '42',
        label: 'Ada Lovelace',
        resourceLabel: 'Contacto',
        observations: ['Falta empresa'],
        actions: [
            new SamAction('open', 'Abrir ficha', 'Ver el contacto', 'https://example.test/contacts/42'),
        ],
        continuePrompt: 'Revisa este contacto',
    );

    $brief = app(SamRuntime::class)->briefFor(
        User::factory()->create(),
        new SamContext(source: 'fake-brief', resource: 'contact', id: '42', label: 'Ada Lovelace'),
    );

    expect($brief)->not->toBeNull()
        ->and($brief?->label)->toBe('Ada Lovelace')
        ->and($brief?->observations)->toBe(['Falta empresa'])
        ->and($brief?->actions[0]->key)->toBe('open')
        ->and($brief?->continuePrompt)->toBe('Revisa este contacto');
});

it('answers no brief when the screen has no record in focus', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeBrief();

    FakeSamBriefContributor::$brief = new SamBrief(source: 'fake-brief');

    expect(app(SamRuntime::class)->briefFor(User::factory()->create()))->toBeNull();
});

it('answers no brief when the module does not present one', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeBrief();

    expect(app(SamRuntime::class)->briefFor(
        User::factory()->create(),
        new SamContext(source: 'fake-brief', resource: 'contact', id: '42'),
    ))->toBeNull();
});

it('denies the brief without the surface permission', function (): void {
    configureSam();
    registerFakeBrief();

    FakeSamBriefContributor::$brief = new SamBrief(
        source: 'fake-brief',
        resource: 'contact',
        id: '42',
        label: 'Ada Lovelace',
    );

    expect(app(SamRuntime::class)->briefFor(
        User::factory()->create(),
        new SamContext(source: 'fake-brief', resource: 'contact', id: '42'),
    ))->toBeNull();
});
