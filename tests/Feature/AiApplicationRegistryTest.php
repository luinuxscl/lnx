<?php

use App\Ai\Agents\ConnectionTestAgent;
use App\Ai\AiApplicationRegistry;
use App\Ai\AiConfig;
use App\Ai\AiGateway;
use App\Ai\Exceptions\AiQuotaExceededException;
use App\Models\AiQuotaCounter;
use App\Models\AiUsageEvent;
use App\Models\User;

beforeEach(function () {
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.provider', 'openrouter');
    config()->set('lnx_ai.default_model', 'test/model');
    config()->set('lnx_ai.allowed_models', ['test/model' => ['tools' => false, 'structured' => false, 'streaming' => true, 'context' => 128000]]);
    config()->set('lnx_ai.max_output_tokens', 20);
    config()->set('ai.providers.openrouter.key', 'test-key');
});

it('enforces the quota a package declares for its application', function () {
    config()->set('lnx_ai.limits.user', ['window' => 'hour', 'requests' => 0, 'tokens' => 0]);
    config()->set('lnx_ai.limits.default', ['window' => 'hour', 'requests' => 50, 'tokens' => 0]);
    config()->set('lnx_ai.limits.applications', []);
    $this->actingAs(User::factory()->create());

    app(AiApplicationRegistry::class)->register('luz', [
        'luz.extraction' => ['window' => 'hour', 'requests' => 1, 'tokens' => 0],
    ]);

    ConnectionTestAgent::fake(['ok', 'ok']);

    $gateway = app(AiGateway::class);
    $gateway->prompt(new ConnectionTestAgent, 'first', application: 'luz.extraction');

    expect(fn () => $gateway->prompt(new ConnectionTestAgent, 'second', application: 'luz.extraction'))
        ->toThrow(AiQuotaExceededException::class);

    expect(AiQuotaCounter::query()->where('scope_type', 'application')->where('scope_id', 'luz.extraction')->value('requests'))->toBe(1)
        ->and(AiUsageEvent::query()->where('application', 'luz.extraction')->count())->toBe(2);

    ConnectionTestAgent::assertPromptedTimes(1);
});

it('lets configuration override a package-declared quota', function () {
    config()->set('lnx_ai.limits.applications', [
        'luz.search' => ['window' => 'hour', 'requests' => 3, 'tokens' => 0],
    ]);

    app(AiApplicationRegistry::class)->register('luz', [
        'luz.search' => ['window' => 'hour', 'requests' => 1, 'tokens' => 0],
    ]);

    expect(app(AiConfig::class)->applicationLimit('luz.search')['requests'])->toBe(3);
});

it('falls back to the default quota when a package declares an application without one', function () {
    config()->set('lnx_ai.limits.default', ['window' => 'hour', 'requests' => 7, 'tokens' => 0]);

    app(AiApplicationRegistry::class)->register('luz', ['luz.notes' => []]);

    expect(app(AiConfig::class)->applicationLimit('luz.notes')['requests'])->toBe(7);
});

it('lists package-declared applications among the configured ones', function () {
    config()->set('lnx_ai.limits.applications', [
        'document_extraction' => ['window' => 'hour', 'requests' => 20, 'tokens' => 0],
    ]);

    app(AiApplicationRegistry::class)->register('luz', [
        'luz.analysis' => ['window' => 'day', 'requests' => 5, 'tokens' => 1000],
    ]);

    $limits = app(AiConfig::class)->applicationLimits();

    expect($limits)->toHaveKeys(['document_extraction', 'luz.analysis'])
        ->and($limits['luz.analysis']['window'])->toBe('day')
        ->and($limits['luz.analysis']['requests'])->toBe(5)
        ->and($limits['luz.analysis']['tokens'])->toBe(1000);
});

it('rejects an application that is not prefixed with the package source', function () {
    app(AiApplicationRegistry::class)->register('luz', ['extraction' => []]);
})->throws(InvalidArgumentException::class);
