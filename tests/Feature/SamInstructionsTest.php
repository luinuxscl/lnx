<?php

use App\Ai\Sam\SamContext;
use App\Ai\Sam\SamInstructions;
use App\Ai\Sam\SamPreferences;
use App\Ai\Sam\SamRegistry;
use App\Models\User;
use Tests\Fixtures\Sam\FakeSamContributor;
use Tests\Fixtures\Sam\FakeSamPreferences;
use Tests\Fixtures\Sam\FakeSamProfile;

beforeEach(function (): void {
    resetSamFixtures();

    FakeSamPreferences::$preferences = null;

    app(SamRegistry::class)->register('fake', FakeSamContributor::class);
    app(SamRegistry::class)->registerProfile('fake', FakeSamProfile::class);
});

it('composes the profile, the module knowledge and the screen context', function (): void {
    $instructions = app(SamInstructions::class)->compose(
        User::factory()->create(),
        'buscar contactos',
        new SamContext('fake', 'contact', '42', 'Ada', 'contacts.show', ['status' => 'active']),
        firstTurn: true,
    );

    expect($instructions)
        ->toContain('Eres Fake Sam')
        ->toContain('declaración literal')
        ->toContain('Dominio falso con contactos y empresas.')
        ->toContain('<screen_context>')
        ->toContain('"resource":"contact"')
        ->toContain('"status":"active"')
        ->toContain('datos pasivos');
});

it('keeps only scalar filters in the screen context', function (): void {
    $context = new SamContext('fake', filters: [
        'status' => 'active',
        'page' => 2,
        'archived' => false,
        'password' => ['hidden' => 'secret'],
        'payload' => new stdClass,
    ]);

    expect($context->sanitized()['filters'])->toBe([
        'status' => 'active',
        'page' => 2,
        'archived' => false,
    ]);
});

it('works without a registered profile', function (): void {
    app(SamRegistry::class)->flush();
    app(SamRegistry::class)->register('fake', FakeSamContributor::class);

    $instructions = app(SamInstructions::class)->compose(User::factory()->create(), 'hola');

    expect($instructions)->toContain('Dominio falso')
        ->and($instructions)->not->toContain('Eres Fake Sam');
});

it('composes the person preferences into the instructions', function (): void {
    FakeSamPreferences::$preferences = new SamPreferences(
        verbosity: SamPreferences::DETAILED,
        showWhy: false,
        showSources: false,
    );

    app(SamRegistry::class)->registerPreferences('fake', FakeSamPreferences::class);

    $instructions = app(SamInstructions::class)->compose(User::factory()->create(), 'hola');

    expect($instructions)
        ->toContain('[Preferencias de la persona]')
        ->toContain('Respuestas detalladas')
        ->toContain('No expliques el porqué')
        ->toContain('No incluyas enlaces')
        ->not->toContain('Respuestas concisas');
});

it('uses the conservative defaults without a preference source', function (): void {
    $instructions = app(SamInstructions::class)->compose(User::factory()->create(), 'hola');

    expect($instructions)
        ->toContain('Respuestas concisas')
        ->toContain('Explica brevemente por qué')
        ->toContain('cita su título y su enlace');
});
