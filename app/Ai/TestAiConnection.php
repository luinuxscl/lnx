<?php

namespace App\Ai;

use App\Ai\Agents\ConnectionTestAgent;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs a single, explicit and limited connection probe against the provider.
 *
 * It is never invoked while rendering a page. Failures are reduced to a
 * category and logged without the exception message, so no secret or response
 * body is persisted or shown.
 */
class TestAiConnection
{
    public function __construct(
        private readonly AiGateway $gateway,
        private readonly AiConfig $config,
        private readonly AiErrorCategorizer $categorizer,
    ) {}

    /**
     * Whether the probe can run against a ready configuration.
     */
    public function isReady(): bool
    {
        return $this->config->isReady();
    }

    /**
     * Execute the probe and return a secret-free result.
     *
     * @return array{
     *     status: string,
     *     category: string,
     *     duration_ms: int,
     *     provider: string,
     *     model: string|null,
     *     input_tokens: int|null,
     *     output_tokens: int|null,
     *     issues: list<string>
     * }
     */
    public function handle(): array
    {
        $model = $this->config->defaultModel();

        if (! $this->isReady()) {
            return $this->result('error', AiErrorCategorizer::NOT_CONFIGURED, 0, $model);
        }

        $startedAt = microtime(true);

        try {
            $agent = new ConnectionTestAgent;

            $response = $this->gateway->prompt(
                $agent,
                'Connection test: reply with "ok".',
                timeout: $agent->timeout(),
                application: 'connection_test',
                reservedTokens: $agent->maxTokens(),
            );

            return [
                'status' => 'success',
                'category' => AiErrorCategorizer::OK,
                'duration_ms' => $this->elapsedMilliseconds($startedAt),
                'provider' => $this->config->providerName(),
                'model' => $response->meta->model ?? $model,
                'input_tokens' => $response->usage->inputTokens,
                'output_tokens' => $response->usage->outputTokens,
                'issues' => [],
            ];
        } catch (Throwable $exception) {
            $category = $this->categorizer->categorize($exception);

            Log::warning('AI connection test failed.', [
                'provider' => $this->config->providerName(),
                'category' => $category,
                'exception' => $exception::class,
            ]);

            return $this->result('error', $category, $this->elapsedMilliseconds($startedAt), $model);
        }
    }

    /**
     * Build a result for a failure that produced no usage.
     *
     * @return array{
     *     status: string,
     *     category: string,
     *     duration_ms: int,
     *     provider: string,
     *     model: string|null,
     *     input_tokens: int|null,
     *     output_tokens: int|null,
     *     issues: list<string>
     * }
     */
    protected function result(string $status, string $category, int $durationMs, ?string $model): array
    {
        return [
            'status' => $status,
            'category' => $category,
            'duration_ms' => $durationMs,
            'provider' => $this->config->providerName(),
            'model' => $model,
            'input_tokens' => null,
            'output_tokens' => null,
            'issues' => $this->config->issues(),
        ];
    }

    /**
     * Milliseconds elapsed since a start timestamp.
     */
    protected function elapsedMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
