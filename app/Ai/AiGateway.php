<?php

namespace App\Ai;

use App\Ai\Exceptions\AiNotConfiguredException;
use App\Ai\Exceptions\AiQuotaExceededException;
use App\Ai\Exceptions\ModelNotAllowedException;
use App\Ai\Exceptions\UnsupportedCapabilityException;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Files\File;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Throwable;

/**
 * Single entry point for AI requests.
 *
 * It enforces, server-side, that AI is enabled, the model is allowlisted and
 * the model declares every required capability before the provider is called.
 * It also reserves local quota atomically and records consumption for every
 * attempt, so a feature cannot bypass limits by calling the provider directly.
 * The provider and model are always passed explicitly, so the SDK never
 * switches to another provider or model without explicit configuration.
 */
class AiGateway
{
    public function __construct(
        private readonly AiConfig $config,
        private readonly AiQuota $quota,
        private readonly RecordAiUsage $usage,
        private readonly AiErrorCategorizer $categorizer,
    ) {}

    /**
     * Whether the gateway is ready to serve requests, optionally for a model.
     */
    public function isReady(?string $model = null): bool
    {
        return $this->config->isReady($model);
    }

    /**
     * Resolve and validate the model for a request.
     *
     * @param  list<string>  $capabilities
     *
     * @throws AiNotConfiguredException
     * @throws ModelNotAllowedException
     * @throws UnsupportedCapabilityException
     */
    public function resolveModel(?string $model, array $capabilities = []): string
    {
        if (! $this->config->enabled()) {
            throw new AiNotConfiguredException('disabled');
        }

        if (! $this->config->providerConfigured()) {
            throw new AiNotConfiguredException('provider');
        }

        $model ??= $this->config->defaultModel();

        if ($model === null) {
            throw new AiNotConfiguredException('model');
        }

        if (! $this->config->isModelAllowed($model)) {
            throw new ModelNotAllowedException($model);
        }

        $missing = $this->config->missingCapabilities($model, $capabilities);

        if ($missing !== []) {
            throw new UnsupportedCapabilityException($model, $missing);
        }

        return $model;
    }

    /**
     * Run a prompt through an agent, validated, quota-controlled and recorded.
     *
     * @param  list<string>  $capabilities
     * @param  array<int, File|UploadedFile>  $attachments
     *
     * @throws AiQuotaExceededException when a local limit is reached before the call
     */
    public function prompt(
        Agent $agent,
        string $prompt,
        ?string $model = null,
        array $capabilities = [],
        ?int $timeout = null,
        ?string $application = null,
        ?int $reservedTokens = null,
        ?string $correlationId = null,
        array $attachments = [],
    ): AgentResponse {
        $resolved = $this->resolveModel($model, $capabilities);

        $application = $this->config->normalizeApplication($application);
        $reservedTokens ??= $this->config->maxOutputTokens();
        $correlationId ??= (string) str()->uuid();

        $user = auth()->user();
        $user = $user instanceof User ? $user : null;

        try {
            $reservation = $this->quota->reserve($user, $application, $reservedTokens, $correlationId);
        } catch (AiQuotaExceededException $exception) {
            $this->usage->blocked($correlationId, $user, $application, $resolved);

            throw $exception;
        }

        $startedAt = microtime(true);

        try {
            $response = $agent->prompt(
                $prompt,
                $attachments,
                provider: $this->config->provider(),
                model: $resolved,
                timeout: $timeout,
            );
        } catch (Throwable $exception) {
            $this->quota->release($reservation);
            $this->usage->failure(
                $correlationId,
                $user,
                $application,
                $resolved,
                $this->elapsedMilliseconds($startedAt),
                $this->categorizer->categorize($exception),
            );

            throw $exception;
        }

        $this->quota->reconcile($reservation, $response->usage->totalTokens());
        $this->usage->success(
            $correlationId,
            $user,
            $application,
            $resolved,
            $response,
            $this->elapsedMilliseconds($startedAt),
        );

        return $response;
    }

    /**
     * Run a prompt through an agent as a streamable response, validated, quota-controlled and recorded.
     *
     * @param  list<string>  $capabilities
     * @param  array<int, File|UploadedFile>  $attachments
     *
     * @throws AiQuotaExceededException when a local limit is reached before the call
     */
    public function stream(
        Agent $agent,
        string $prompt,
        ?string $model = null,
        array $capabilities = ['streaming'],
        ?int $timeout = null,
        ?string $application = null,
        ?int $reservedTokens = null,
        ?string $correlationId = null,
        array $attachments = [],
    ): StreamableAgentResponse {
        $capabilities = array_values(array_unique(array_merge(['streaming'], $capabilities)));
        $resolved = $this->resolveModel($model, $capabilities);

        $application = $this->config->normalizeApplication($application);
        $reservedTokens ??= $this->config->maxOutputTokens();
        $correlationId ??= (string) str()->uuid();

        $user = auth()->user();
        $user = $user instanceof User ? $user : null;

        try {
            $reservation = $this->quota->reserve($user, $application, $reservedTokens, $correlationId);
        } catch (AiQuotaExceededException $exception) {
            $this->usage->blocked($correlationId, $user, $application, $resolved);

            throw $exception;
        }

        $startedAt = microtime(true);

        try {
            $streamable = $agent->stream(
                $prompt,
                $attachments,
                provider: $this->config->provider(),
                model: $resolved,
                timeout: $timeout,
            );
        } catch (Throwable $exception) {
            $this->quota->release($reservation);
            $this->usage->failure(
                $correlationId,
                $user,
                $application,
                $resolved,
                $this->elapsedMilliseconds($startedAt),
                $this->categorizer->categorize($exception),
            );

            throw $exception;
        }

        $streamable->then(function ($response) use ($reservation, $correlationId, $user, $application, $resolved, $startedAt): void {
            $totalTokens = $response->usage?->totalTokens();
            $this->quota->reconcile($reservation, $totalTokens);
            $this->usage->success(
                $correlationId,
                $user,
                $application,
                $resolved,
                $response,
                $this->elapsedMilliseconds($startedAt),
            );
        });

        $streamable->catch(function ($exception) use ($reservation, $correlationId, $user, $application, $resolved, $startedAt): void {
            $this->quota->release($reservation);
            $this->usage->failure(
                $correlationId,
                $user,
                $application,
                $resolved,
                $this->elapsedMilliseconds($startedAt),
                $this->categorizer->categorize($exception),
            );
        });

        return $streamable;
    }

    /**
     * Milliseconds elapsed since a start timestamp.
     */
    protected function elapsedMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
