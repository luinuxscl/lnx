<?php

use App\Ai\AiGateway;
use App\Ai\Exceptions\AiNotConfiguredException;
use App\Ai\Exceptions\ModelNotAllowedException;
use App\Ai\Exceptions\UnsupportedCapabilityException;

it('rejects requests while AI is disabled', function () {
    config()->set('lnx_ai.enabled', false);

    expect(fn () => app(AiGateway::class)->resolveModel(null))
        ->toThrow(AiNotConfiguredException::class);
});

it('rejects a model outside the server-side allowlist', function () {
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.default_model', 'test/model');
    config()->set('lnx_ai.allowed_models', ['test/model' => []]);
    config()->set('ai.providers.openrouter.key', 'test-key');

    expect(fn () => app(AiGateway::class)->resolveModel('other/model'))
        ->toThrow(ModelNotAllowedException::class);
});

it('rejects required capabilities the model does not declare', function () {
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.default_model', 'test/model');
    config()->set('lnx_ai.allowed_models', ['test/model' => ['tools' => false]]);
    config()->set('ai.providers.openrouter.key', 'test-key');

    expect(fn () => app(AiGateway::class)->resolveModel('test/model', ['tools']))
        ->toThrow(UnsupportedCapabilityException::class);
});

it('resolves an allowed model that declares the required capabilities', function () {
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.default_model', 'test/model');
    config()->set('lnx_ai.allowed_models', ['test/model' => ['tools' => true, 'structured' => false]]);
    config()->set('ai.providers.openrouter.key', 'test-key');

    expect(app(AiGateway::class)->resolveModel('test/model', ['tools']))->toBe('test/model');
});
