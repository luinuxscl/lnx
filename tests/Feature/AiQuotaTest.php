<?php

use App\Ai\Agents\ConnectionTestAgent;
use App\Ai\AiGateway;
use App\Ai\Exceptions\AiQuotaExceededException;
use App\Ai\RecordAiUsage;
use App\Models\AiQuotaCounter;
use App\Models\AiUsageEvent;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;

function quotaConfigureAi(): void
{
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.provider', 'openrouter');
    config()->set('lnx_ai.default_model', 'test/model');
    config()->set('lnx_ai.allowed_models', ['test/model' => ['tools' => false, 'structured' => false, 'streaming' => true, 'context' => 128000]]);
    config()->set('lnx_ai.max_output_tokens', 20);
    config()->set('ai.providers.openrouter.key', 'test-key');
}

function quotaUser(): User
{
    return User::factory()->create();
}

function usageResponse(int $input = 100, int $output = 50, ?float $reportedCost = null): AgentResponse
{
    $response = new AgentResponse('fake-invocation', 'ok', new TextUsage($input, $output), new Meta('openrouter', 'test/model'));

    if ($reportedCost !== null) {
        $raw = new Illuminate\Http\Client\Response(new Response(200, [], (string) json_encode([
            'usage' => ['cost' => $reportedCost],
        ])));

        return $response->withRawResponse($raw);
    }

    return $response;
}

beforeEach(function () {
    quotaConfigureAi();
});

it('blocks a request once the per-user request limit is reached', function () {
    config()->set('lnx_ai.limits.user', ['window' => 'hour', 'requests' => 1, 'tokens' => 0]);
    config()->set('lnx_ai.limits.default', ['window' => 'hour', 'requests' => 0, 'tokens' => 0]);
    config()->set('lnx_ai.limits.applications', []);
    $user = quotaUser();
    $this->actingAs($user);

    ConnectionTestAgent::fake(['ok', 'ok']);

    $gateway = app(AiGateway::class);
    $gateway->prompt(new ConnectionTestAgent, 'first', application: 'feature_a');

    expect(fn () => $gateway->prompt(new ConnectionTestAgent, 'second', application: 'feature_a'))
        ->toThrow(AiQuotaExceededException::class);

    expect(AiUsageEvent::query()->where('status', AiUsageEvent::STATUS_BLOCKED)->count())->toBe(1);
    expect(AiQuotaCounter::query()->where('scope_type', 'user')->value('requests'))->toBe(1);

    ConnectionTestAgent::assertPromptedTimes(1);
});

it('enforces the per-application limit independently of the user limit', function () {
    config()->set('lnx_ai.limits.user', ['window' => 'hour', 'requests' => 0, 'tokens' => 0]);
    config()->set('lnx_ai.limits.default', ['window' => 'hour', 'requests' => 0, 'tokens' => 0]);
    config()->set('lnx_ai.limits.applications', [
        'feature_a' => ['window' => 'hour', 'requests' => 1, 'tokens' => 0],
        'feature_b' => ['window' => 'hour', 'requests' => 5, 'tokens' => 0],
    ]);
    config()->set('lnx_ai.max_output_tokens', 20);
    $this->actingAs(quotaUser());

    ConnectionTestAgent::fake(['ok', 'ok', 'ok']);

    $gateway = app(AiGateway::class);
    $gateway->prompt(new ConnectionTestAgent, 'a1', application: 'feature_a');

    expect(fn () => $gateway->prompt(new ConnectionTestAgent, 'a2', application: 'feature_a'))
        ->toThrow(AiQuotaExceededException::class);

    $gateway->prompt(new ConnectionTestAgent, 'b1', application: 'feature_b');

    expect(AiQuotaCounter::query()->where('scope_type', 'application')->where('scope_id', 'feature_a')->value('requests'))->toBe(1);
    expect(AiQuotaCounter::query()->where('scope_type', 'application')->where('scope_id', 'feature_b')->value('requests'))->toBe(1);
});

it('blocks before the provider call when the reserved tokens exceed the token limit', function () {
    config()->set('lnx_ai.limits.user', ['window' => 'hour', 'requests' => 0, 'tokens' => 10]);
    config()->set('lnx_ai.limits.default', ['window' => 'hour', 'requests' => 0, 'tokens' => 0]);
    config()->set('lnx_ai.limits.applications', []);
    $this->actingAs(quotaUser());

    ConnectionTestAgent::fake(['ok']);

    expect(fn () => app(AiGateway::class)->prompt(new ConnectionTestAgent, 'hi', application: 'feature_a', reservedTokens: 50))
        ->toThrow(AiQuotaExceededException::class);

    ConnectionTestAgent::assertNeverPrompted();
});

it('releases the reservation on failure but keeps the attempt recorded', function () {
    config()->set('lnx_ai.limits.user', ['window' => 'hour', 'requests' => 100, 'tokens' => 100000]);
    config()->set('lnx_ai.limits.default', ['window' => 'hour', 'requests' => 100, 'tokens' => 100000]);
    config()->set('lnx_ai.limits.applications', []);
    $this->actingAs(quotaUser());

    ConnectionTestAgent::fake(function (): never {
        throw new RuntimeException('provider exploded');
    });

    expect(fn () => app(AiGateway::class)->prompt(new ConnectionTestAgent, 'hi', application: 'feature_a'))
        ->toThrow(RuntimeException::class);

    $counter = AiQuotaCounter::query()->where('scope_type', 'user')->firstOrFail();

    expect($counter->requests)->toBe(1)
        ->and($counter->reserved_tokens)->toBe(0)
        ->and($counter->tokens)->toBe(0);

    $event = AiUsageEvent::query()->firstOrFail();
    expect($event->status)->toBe(AiUsageEvent::STATUS_ERROR)
        ->and($event->total_tokens)->toBeNull()
        ->and($event->cost_source)->toBe(AiUsageEvent::COST_UNKNOWN);
});

it('reconciles the reservation with the actual token usage on success', function () {
    config()->set('lnx_ai.limits.user', ['window' => 'hour', 'requests' => 0, 'tokens' => 100000]);
    config()->set('lnx_ai.limits.default', ['window' => 'hour', 'requests' => 0, 'tokens' => 100000]);
    config()->set('lnx_ai.limits.applications', []);
    $this->actingAs(quotaUser());

    ConnectionTestAgent::fake([usageResponse(120, 30)]);

    app(AiGateway::class)->prompt(new ConnectionTestAgent, 'hi', application: 'feature_a');

    $counter = AiQuotaCounter::query()->where('scope_type', 'user')->firstOrFail();

    expect($counter->reserved_tokens)->toBe(0)
        ->and($counter->tokens)->toBe(150);
});

it('groups retries under one logical correlation id and numbers the attempts', function () {
    $user = quotaUser();
    $this->actingAs($user);
    $recorder = app(RecordAiUsage::class);
    $correlationId = 'corr-123';

    $first = $recorder->success($correlationId, $user, 'feature_a', 'test/model', usageResponse(), 10);
    $second = $recorder->failure($correlationId, $user, 'feature_a', 'test/model', 20, 'unavailable');

    expect($first->correlation_id)->toBe($correlationId)
        ->and($first->attempt)->toBe(1)
        ->and($second->correlation_id)->toBe($correlationId)
        ->and($second->attempt)->toBe(2);
});

it('records a cost reported by the provider without estimating', function () {
    config()->set('lnx_ai.pricing.models', ['test/model' => ['input_per_million' => 1000, 'output_per_million' => 1000]]);
    config()->set('lnx_ai.pricing.version', 'v1');
    $user = quotaUser();
    $this->actingAs($user);

    app(RecordAiUsage::class)->success('corr-1', $user, 'feature_a', 'test/model', usageResponse(100, 50, 0.001234), 10);

    $event = AiUsageEvent::query()->firstOrFail();

    expect($event->cost_source)->toBe(AiUsageEvent::COST_REPORTED)
        ->and((float) $event->reported_cost)->toBe(0.001234)
        ->and($event->estimated_cost)->toBeNull()
        ->and($event->cost_currency)->toBe('USD');
});

it('estimates a cost from configured rates and records their version', function () {
    config()->set('lnx_ai.pricing.version', '2026-10');
    config()->set('lnx_ai.pricing.currency', 'EUR');
    config()->set('lnx_ai.pricing.models', ['test/model' => ['input_per_million' => 1000, 'output_per_million' => 2000]]);
    $user = quotaUser();
    $this->actingAs($user);

    app(RecordAiUsage::class)->success('corr-1', $user, 'feature_a', 'test/model', usageResponse(100, 50), 10);

    $event = AiUsageEvent::query()->firstOrFail();

    expect($event->cost_source)->toBe(AiUsageEvent::COST_ESTIMATED)
        ->and((float) $event->estimated_cost)->toBe(0.2)
        ->and($event->reported_cost)->toBeNull()
        ->and($event->cost_currency)->toBe('EUR')
        ->and($event->pricing_version)->toBe('2026-10');
});

it('marks a missing cost as unknown instead of zero', function () {
    config()->set('lnx_ai.pricing.models', []);
    config()->set('lnx_ai.pricing.version', null);
    $user = quotaUser();
    $this->actingAs($user);

    app(RecordAiUsage::class)->success('corr-1', $user, 'feature_a', 'test/model', usageResponse(), 10);

    $event = AiUsageEvent::query()->firstOrFail();

    expect($event->cost_source)->toBe(AiUsageEvent::COST_UNKNOWN)
        ->and($event->reported_cost)->toBeNull()
        ->and($event->estimated_cost)->toBeNull();
});
