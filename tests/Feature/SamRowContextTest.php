<?php

use App\Models\User;
use Luinuxscl\LnxSam\SamContext;
use Luinuxscl\LnxSam\SamInstructions;
use Luinuxscl\LnxSam\SamRegistry;
use Luinuxscl\LnxSam\SamRuntime;
use Luinuxscl\LnxSam\SamSignal;
use Tests\Fixtures\Sam\FakeSamContributor;

beforeEach(function (): void {
    resetSamFixtures();
});

function registerFakeSamForRows(): void
{
    app(SamRegistry::class)->register('fake', FakeSamContributor::class);
}

it('resolves the context of a focused row and attaches its signals', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeSamForRows();

    FakeSamContributor::$rowContext = new SamContext(
        source: 'fake',
        resource: 'contact',
        id: '42',
        label: 'Ada Lovelace',
        route: 'fake.contact',
        filters: ['status' => 'active'],
    );

    FakeSamContributor::$signals = [
        new SamSignal('missing_company', true, 'https://example.test/contacts/42'),
        new SamSignal('duplicate_email', 2),
    ];

    $context = app(SamRuntime::class)->contextForRow(User::factory()->create(), 'contact', '42');

    expect($context)->not->toBeNull()
        ->and($context?->label)->toBe('Ada Lovelace')
        ->and($context?->signals)->toHaveCount(2)
        ->and($context?->signals[0]->name)->toBe('missing_company');
});

it('answers no context when the module does not resolve the row', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeSamForRows();

    $runtime = app(SamRuntime::class);
    $actor = User::factory()->create();

    FakeSamContributor::$rowContext = null;

    expect($runtime->contextForRow($actor, 'contact', '99'))->toBeNull()
        ->and($runtime->contextForRow($actor, '', '99'))->toBeNull()
        ->and($runtime->contextForRow($actor, 'contact', '   '))->toBeNull();
});

it('refuses a row context without the surface permission', function (): void {
    configureSam();
    registerFakeSamForRows();

    FakeSamContributor::$rowContext = new SamContext(source: 'fake', resource: 'contact', id: '42');

    expect(app(SamRuntime::class)->contextForRow(User::factory()->create(), 'contact', '42'))->toBeNull();
});

it('sanitizes the signals into the prompt of the context', function (): void {
    $context = new SamContext(
        source: 'fake',
        resource: 'contact',
        id: '42',
        signals: [
            new SamSignal('missing_owner', true, 'https://example.test/contacts/42'),
            new SamSignal('duplicate_name', 3),
            new SamSignal('integrity', 'empresa archivada'),
        ],
    );

    $prompt = $context->toPrompt();

    expect($prompt)->toContain('"name":"missing_owner","value":true')
        ->and($prompt)->toContain('"name":"duplicate_name","value":3')
        ->and($prompt)->toContain('"name":"integrity","value":"empresa archivada"');
});

it('takes the signals of a routed context too', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeSamForRows();

    FakeSamContributor::$context = new SamContext(source: 'fake', resource: 'contact', id: '42');
    FakeSamContributor::$signals = [new SamSignal('missing_channel', true)];

    $user = User::factory()->create();
    $this->actingAs($user);

    $context = app(SamRuntime::class)->contextFor(request());

    expect($context?->signals)->toHaveCount(1)
        ->and($context?->signals[0]->name)->toBe('missing_channel');
});

it('tells the model that the signals of the context are already computed', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeSamForRows();

    FakeSamContributor::$context = new SamContext(
        source: 'fake',
        resource: 'contact',
        id: '42',
        signals: [new SamSignal('missing_company', true)],
    );

    $this->actingAs(User::factory()->create());

    $prompt = app(SamInstructions::class)->compose(
        auth()->user(),
        'Que le falta a este contacto',
        FakeSamContributor::$context,
    );

    expect($prompt)->toContain('"name":"missing_company","value":true')
        ->and($prompt)->toContain('ya están calculadas por el módulo')
        ->and($prompt)->toContain('Úsalas tal cual');
});
