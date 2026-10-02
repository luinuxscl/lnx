<?php

namespace App\Ai;

use App\Ai\Exceptions\AiNotConfiguredException;
use App\Ai\Exceptions\ModelNotAllowedException;
use App\Ai\Exceptions\UnsupportedCapabilityException;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Responses\AgentResponse;

/**
 * Single entry point for AI requests.
 *
 * It enforces, server-side, that AI is enabled, the model is allowlisted and
 * the model declares every required capability before the provider is called.
 * The provider and model are always passed explicitly, so the SDK never
 * switches to another provider or model without explicit configuration.
 */
class AiGateway
{
    public function __construct(private readonly AiConfig $config) {}

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
     * Run a prompt through an agent, validated and without automatic failover.
     *
     * @param  list<string>  $capabilities
     */
    public function prompt(
        Agent $agent,
        string $prompt,
        ?string $model = null,
        array $capabilities = [],
        ?int $timeout = null,
    ): AgentResponse {
        $resolved = $this->resolveModel($model, $capabilities);

        return $agent->prompt(
            $prompt,
            provider: $this->config->provider(),
            model: $resolved,
            timeout: $timeout,
        );
    }
}
