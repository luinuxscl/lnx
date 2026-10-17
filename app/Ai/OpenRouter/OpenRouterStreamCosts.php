<?php

namespace App\Ai\OpenRouter;

/**
 * Request-scoped accumulator of the cost OpenRouter reports for streamed runs.
 *
 * A streamed run can span several generation steps that share one invocation
 * id, so costs are summed. The value is pulled (read and cleared) when the
 * usage row is recorded, so it never leaks across requests.
 */
class OpenRouterStreamCosts
{
    /**
     * Reported cost per invocation id.
     *
     * @var array<string, float>
     */
    private array $costs = [];

    /**
     * Add a reported cost to a run.
     */
    public function record(string $invocationId, float $cost): void
    {
        $this->costs[$invocationId] = ($this->costs[$invocationId] ?? 0.0) + $cost;
    }

    /**
     * Read and clear the reported cost for a run.
     */
    public function pull(string $invocationId): ?float
    {
        $cost = $this->costs[$invocationId] ?? null;

        unset($this->costs[$invocationId]);

        return $cost;
    }
}
