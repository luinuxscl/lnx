<?php

namespace App\Ai\OpenRouter;

/**
 * Reads the cost OpenRouter reports inside a streamed (SSE) response body.
 *
 * OpenRouter returns `usage.cost` on the final chunk of every generation step,
 * but the Laravel AI SDK discards it for streams. This parser recovers it from
 * the raw body so a streamed run can be recorded like a non-streamed one.
 */
class StreamCostParser
{
    /**
     * Sum every `usage.cost` carried by the SSE `data:` lines.
     *
     * Returns null when no line reports a numeric cost, so a missing cost stays
     * unknown rather than zero.
     */
    public function parse(string $body): ?float
    {
        $cost = null;

        foreach (preg_split('/\r\n|\r|\n/', $body) ?: [] as $line) {
            $line = trim($line);

            if (! str_starts_with($line, 'data:')) {
                continue;
            }

            $payload = trim(substr($line, 5));

            if ($payload === '' || $payload === '[DONE]') {
                continue;
            }

            $decoded = json_decode($payload, true);

            if (! is_array($decoded)) {
                continue;
            }

            $value = $decoded['usage']['cost'] ?? null;

            if (is_numeric($value)) {
                $cost = ($cost ?? 0.0) + (float) $value;
            }
        }

        return $cost;
    }
}
