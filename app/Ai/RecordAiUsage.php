<?php

namespace App\Ai;

use App\Models\AiUsageEvent;
use App\Models\User;
use Laravel\Ai\Responses\AgentResponse;

/**
 * Persists one consumption row per provider attempt.
 *
 * Rows sharing a correlation id form a single logical request, so retries do
 * not duplicate the logical record while every attempt that could have
 * generated consumption is still counted. A missing cost is stored as null and
 * marked unknown, never as zero.
 */
class RecordAiUsage
{
    /**
     * Currency reported by OpenRouter for the cost it returns.
     */
    public const PROVIDER_CURRENCY = 'USD';

    public function __construct(private readonly AiConfig $config) {}

    /**
     * Record a successful attempt, reading tokens and reported cost.
     *
     * A caller that captured the cost itself (streamed runs, whose raw response
     * the SDK does not expose) passes it explicitly; otherwise it is read from
     * the response.
     */
    public function success(
        string $correlationId,
        ?User $user,
        string $application,
        string $requestedModel,
        AgentResponse $response,
        int $durationMs,
        ?float $reportedCost = null,
    ): AiUsageEvent {
        $inputTokens = $response->usage->inputTokens;
        $outputTokens = $response->usage->outputTokens;
        $effectiveModel = $response->meta->model ?: $requestedModel;

        $reportedCost ??= $this->extractReportedCost($response);

        $attributes = [
            'estimated_cost' => null,
            'cost_currency' => null,
            'pricing_version' => null,
        ];

        if ($reportedCost !== null) {
            $attributes['reported_cost'] = $reportedCost;
            $attributes['cost_currency'] = self::PROVIDER_CURRENCY;
            $attributes['cost_source'] = AiUsageEvent::COST_REPORTED;
        } else {
            $estimate = $this->config->estimateCost($effectiveModel, $inputTokens, $outputTokens);

            if ($estimate !== null) {
                $attributes['estimated_cost'] = $estimate['amount'];
                $attributes['cost_currency'] = $estimate['currency'];
                $attributes['cost_source'] = AiUsageEvent::COST_ESTIMATED;
                $attributes['pricing_version'] = $estimate['version'];
            } else {
                $attributes['cost_source'] = AiUsageEvent::COST_UNKNOWN;
            }
        }

        return $this->create($correlationId, $user, $application, [
            'requested_model' => $requestedModel,
            'effective_model' => $effectiveModel,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $response->usage->totalTokens(),
            'duration_ms' => $durationMs,
            'status' => AiUsageEvent::STATUS_SUCCESS,
            'error_category' => null,
            ...$attributes,
        ]);
    }

    /**
     * Record a failed attempt that may still have generated consumption.
     */
    public function failure(
        string $correlationId,
        ?User $user,
        string $application,
        string $requestedModel,
        int $durationMs,
        string $errorCategory,
    ): AiUsageEvent {
        return $this->create($correlationId, $user, $application, [
            'requested_model' => $requestedModel,
            'effective_model' => null,
            'input_tokens' => null,
            'output_tokens' => null,
            'total_tokens' => null,
            'duration_ms' => $durationMs,
            'status' => AiUsageEvent::STATUS_ERROR,
            'error_category' => $errorCategory,
            'cost_source' => AiUsageEvent::COST_UNKNOWN,
        ]);
    }

    /**
     * Record an attempt blocked locally before any provider call.
     */
    public function blocked(
        string $correlationId,
        ?User $user,
        string $application,
        string $requestedModel,
    ): AiUsageEvent {
        return $this->create($correlationId, $user, $application, [
            'requested_model' => $requestedModel,
            'effective_model' => null,
            'input_tokens' => null,
            'output_tokens' => null,
            'total_tokens' => null,
            'duration_ms' => null,
            'status' => AiUsageEvent::STATUS_BLOCKED,
            'error_category' => 'quota',
            'cost_source' => AiUsageEvent::COST_UNKNOWN,
        ]);
    }

    /**
     * Create an event, numbering it within its logical request.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function create(string $correlationId, ?User $user, string $application, array $attributes): AiUsageEvent
    {
        $attempt = AiUsageEvent::query()->where('correlation_id', $correlationId)->count() + 1;

        return AiUsageEvent::create([
            'correlation_id' => $correlationId,
            'attempt' => $attempt,
            'user_id' => $user?->getKey(),
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'application' => $this->config->normalizeApplication($application),
            'provider' => $this->config->providerName(),
            'reported_cost' => null,
            'cost_currency' => null,
            'cost_source' => AiUsageEvent::COST_UNKNOWN,
            'pricing_version' => null,
            'recorded_at' => now(),
            ...$attributes,
        ]);
    }

    /**
     * Read the cost reported by the provider, if the raw response carries one.
     */
    protected function extractReportedCost(AgentResponse $response): ?float
    {
        $cost = $response->raw?->json('usage.cost');

        return is_numeric($cost) ? (float) $cost : null;
    }
}
