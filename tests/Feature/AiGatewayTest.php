<?php

use App\Ai\Agents\ConnectionTestAgent;
use App\Ai\AiGateway;
use App\Ai\Exceptions\AiNotConfiguredException;
use App\Ai\Exceptions\ModelNotAllowedException;
use App\Ai\Exceptions\UnsupportedCapabilityException;
use App\Models\User;
use Laravel\Ai\Files\Image;

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

it('resolves a model declared through the environment allowlist', function () {
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.default_model', 'test/model');
    config()->set('lnx_ai.allowed_models', []);
    config()->set('lnx_ai.allowed_models_json', '{"test/model":{"tools":false,"streaming":true}}');
    config()->set('ai.providers.openrouter.key', 'test-key');

    expect(app(AiGateway::class)->resolveModel('test/model'))->toBe('test/model')
        ->and(app(AiGateway::class)->resolveModel('test/model', ['streaming']))->toBe('test/model');
});

it('rejects a vision request when the model does not declare the capability', function () {
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.default_model', 'test/model');
    config()->set('lnx_ai.allowed_models', ['test/model' => ['vision' => false]]);
    config()->set('ai.providers.openrouter.key', 'test-key');

    expect(fn () => app(AiGateway::class)->resolveModel('test/model', ['vision']))
        ->toThrow(UnsupportedCapabilityException::class);
});

it('forwards attachments to the model for a vision request', function () {
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.provider', 'openrouter');
    config()->set('lnx_ai.default_model', 'test/model');
    config()->set('lnx_ai.allowed_models', ['test/model' => ['vision' => true]]);
    config()->set('lnx_ai.limits.user', ['window' => 'hour', 'requests' => 0, 'tokens' => 0]);
    config()->set('lnx_ai.limits.default', ['window' => 'hour', 'requests' => 0, 'tokens' => 0]);
    config()->set('lnx_ai.limits.applications', []);
    config()->set('ai.providers.openrouter.key', 'test-key');

    $this->actingAs(User::factory()->create());

    ConnectionTestAgent::fake(['ok']);

    app(AiGateway::class)->prompt(
        new ConnectionTestAgent,
        'Read the attached document.',
        capabilities: ['vision'],
        application: 'feature_a',
        attachments: [Image::fromBase64(base64_encode('fake-image'), 'image/jpeg')],
    );

    ConnectionTestAgent::assertPrompted(fn ($prompt) => $prompt->prompt === 'Read the attached document.'
        && $prompt->attachments->count() === 1);
});
