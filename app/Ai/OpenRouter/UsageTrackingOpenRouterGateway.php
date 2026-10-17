<?php

namespace App\Ai\OpenRouter;

use Generator;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Gateway\OpenRouter\OpenRouterGateway;
use Laravel\Ai\Gateway\StepContext;
use Laravel\Ai\Gateway\TextGenerationOptions;

/**
 * OpenRouter text gateway that records the cost OpenRouter reports while streaming.
 *
 * The Laravel AI SDK does not expose the raw streamed response, so the body is
 * decorated to keep what the SDK reads and the reported `usage.cost` is parsed
 * once the step completes. Streaming is preserved: nothing is buffered ahead of
 * the first emitted event.
 */
class UsageTrackingOpenRouterGateway extends OpenRouterGateway
{
    public function __construct(
        Dispatcher $events,
        private readonly OpenRouterStreamCosts $costs,
        private readonly StreamCostParser $parser,
    ) {
        parent::__construct($events);
    }

    /**
     * Stream a single Chat Completions step, recording the reported cost.
     *
     * Mirrors the SDK step but wraps the response body so the raw SSE can be
     * parsed for `usage.cost` after the stream is fully consumed.
     */
    public function generateStreamStep(
        string $invocationId,
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages,
        array $tools,
        ?array $schema,
        ?TextGenerationOptions $options,
        ?int $timeout,
        StepContext $stepContext,
    ): Generator {
        $body = $this->buildStepBody($provider, $model, $instructions, $messages, $tools, $schema, $options, $stepContext);

        $body['stream'] = true;

        if (filled($streamOptions = $this->streamOptions($provider))) {
            $body['stream_options'] = $streamOptions;
        }

        $response = $this->withErrorHandling(
            $provider->name(),
            fn () => $this->client($provider, $timeout)
                ->withOptions(['stream' => true])
                ->post('chat/completions', $body),
        );

        $recording = new RecordingStream($response->getBody());

        $result = yield from $this->processTextStream($invocationId, $provider, $model, $recording);

        $cost = $this->parser->parse($recording->recorded());

        if ($cost !== null) {
            $this->costs->record($invocationId, $cost);
        }

        return $result;
    }
}
