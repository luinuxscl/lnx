<?php

use App\Ai\Agents\ConnectionTestAgent;
use App\Ai\AiGateway;
use App\Models\AiUsageEvent;
use Illuminate\Support\Facades\Http;

function configureStreamingCost(): void
{
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.provider', 'openrouter');
    config()->set('lnx_ai.default_model', 'test/stream-model');
    config()->set('lnx_ai.allowed_models', [
        'test/stream-model' => [
            'tools' => false,
            'structured' => false,
            'streaming' => true,
            'context' => 128000,
        ],
    ]);
    config()->set('ai.providers.openrouter.key', 'test-key');
    config()->set('lnx_ai.limits.user', ['window' => 'hour', 'requests' => 0, 'tokens' => 0]);
    config()->set('lnx_ai.limits.default', ['window' => 'hour', 'requests' => 0, 'tokens' => 0]);
    config()->set('lnx_ai.limits.applications', []);
}

/**
 * Build a minimal OpenRouter SSE body, optionally reporting a cost.
 */
function openRouterStreamBody(?float $cost = null): string
{
    $chunks = [
        'data: '.json_encode([
            'id' => 'gen-1',
            'model' => 'test/stream-model',
            'choices' => [['delta' => ['content' => 'Hi'], 'finish_reason' => null]],
        ]),
    ];

    $final = [
        'id' => 'gen-1',
        'model' => 'test/stream-model',
        'choices' => [['delta' => [], 'finish_reason' => 'stop']],
        'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
    ];

    if ($cost !== null) {
        $final['usage']['cost'] = $cost;
    }

    $chunks[] = 'data: '.json_encode($final);
    $chunks[] = 'data: [DONE]';

    return implode("\n\n", $chunks)."\n\n";
}

it('records the cost OpenRouter reports for a streamed run', function () {
    configureStreamingCost();

    Http::fake([
        'openrouter.ai/*' => Http::response(openRouterStreamBody(0.001234), 200, ['Content-Type' => 'text/event-stream']),
    ]);

    $streamable = app(AiGateway::class)->stream(new ConnectionTestAgent, 'ping', application: 'chat');

    foreach ($streamable as $event) {
        // Consume the stream so the run completes and the usage row is recorded.
    }

    $event = AiUsageEvent::query()->where('application', 'chat')->firstOrFail();

    expect($event->status)->toBe(AiUsageEvent::STATUS_SUCCESS)
        ->and($event->cost_source)->toBe(AiUsageEvent::COST_REPORTED)
        ->and((float) $event->reported_cost)->toBe(0.001234)
        ->and($event->cost_currency)->toBe('USD');
});

it('marks a streamed run with no reported cost as unknown', function () {
    configureStreamingCost();

    Http::fake([
        'openrouter.ai/*' => Http::response(openRouterStreamBody(), 200, ['Content-Type' => 'text/event-stream']),
    ]);

    $streamable = app(AiGateway::class)->stream(new ConnectionTestAgent, 'ping', application: 'chat');

    foreach ($streamable as $event) {
        // Consume the stream so the run completes and the usage row is recorded.
    }

    $event = AiUsageEvent::query()->where('application', 'chat')->firstOrFail();

    expect($event->status)->toBe(AiUsageEvent::STATUS_SUCCESS)
        ->and($event->cost_source)->toBe(AiUsageEvent::COST_UNKNOWN)
        ->and($event->reported_cost)->toBeNull();
});
