<?php

namespace App\Ai\OpenRouter;

use Generator;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Ai\Gateway\OpenRouter\OpenRouterGateway;
use Laravel\Ai\Providers\Provider;
use Psr\Http\Message\StreamInterface;

/**
 * OpenRouter text gateway that records the cost OpenRouter reports while streaming.
 *
 * The Laravel AI SDK does not expose the raw streamed response, so the SSE body
 * is read through a recording decorator and the reported `usage.cost` is parsed
 * once the step completes. Streaming is preserved: nothing is buffered ahead of
 * the first emitted event, because the decorator records what the SDK reads.
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
     * @param  StreamInterface  $streamBody
     */
    protected function processTextStream(
        string $invocationId,
        Provider $provider,
        string $model,
        $streamBody,
    ): Generator {
        $recording = new RecordingStream($streamBody);

        $result = yield from parent::processTextStream($invocationId, $provider, $model, $recording);

        $cost = $this->parser->parse($recording->recorded());

        if ($cost !== null) {
            $this->costs->record($invocationId, $cost);
        }

        return $result;
    }
}
