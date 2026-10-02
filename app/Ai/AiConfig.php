<?php

namespace App\Ai;

use Laravel\Ai\Enums\Lab;

/**
 * Read-only access to the AI configuration and its server-side validation.
 *
 * The OpenRouter credential is never exposed here: callers can only ask
 * whether a key is present, never its value.
 */
class AiConfig
{
    /**
     * Capabilities a feature can require from a model.
     *
     * @var list<string>
     */
    public const CAPABILITIES = ['tools', 'structured', 'streaming', 'context'];

    /**
     * Application key used when a feature does not declare one.
     */
    public const DEFAULT_APPLICATION = 'default';

    /**
     * Supported quota window units.
     *
     * @var list<string>
     */
    public const WINDOWS = ['minute', 'hour', 'day'];

    /**
     * Whether AI features have been explicitly enabled.
     */
    public function enabled(): bool
    {
        return (bool) config('lnx_ai.enabled', false);
    }

    /**
     * The configured provider name.
     */
    public function providerName(): string
    {
        $provider = config('lnx_ai.provider', 'openrouter');

        return is_string($provider) && trim($provider) !== '' ? trim($provider) : 'openrouter';
    }

    /**
     * The provider as an enum for the Laravel AI SDK.
     */
    public function provider(): Lab
    {
        return Lab::tryFrom($this->providerName()) ?? Lab::OpenRouter;
    }

    /**
     * Whether the provider has a credential. The key value is never returned.
     */
    public function apiKeyPresent(): bool
    {
        $key = config('ai.providers.'.$this->providerName().'.key');

        return is_string($key) && trim($key) !== '';
    }

    /**
     * Whether the provider is present in the SDK config and has a credential.
     */
    public function providerConfigured(): bool
    {
        $config = config('ai.providers.'.$this->providerName());

        return is_array($config)
            && is_string($config['driver'] ?? null)
            && $this->apiKeyPresent();
    }

    /**
     * The configured default model, or null when it is not set.
     */
    public function defaultModel(): ?string
    {
        $model = config('lnx_ai.default_model');

        return is_string($model) && trim($model) !== '' ? trim($model) : null;
    }

    /**
     * The full allowlist of models and their declared capabilities.
     *
     * @return array<string, mixed>
     */
    public function allowedModels(): array
    {
        $models = config('lnx_ai.allowed_models', []);
        $models = is_array($models) ? $models : [];

        $json = config('lnx_ai.allowed_models_json');

        if (is_string($json) && trim($json) !== '') {
            $decoded = json_decode($json, true);

            if (is_array($decoded)) {
                $models = array_replace($models, $decoded);
            }
        }

        return $models;
    }

    /**
     * Whether a model is present in the server-side allowlist.
     */
    public function isModelAllowed(?string $model): bool
    {
        return $model !== null && array_key_exists($model, $this->allowedModels());
    }

    /**
     * The declared capabilities of a model.
     *
     * @return array<string, mixed>
     */
    public function modelCapabilities(?string $model): array
    {
        if ($model === null) {
            return [];
        }

        $capabilities = $this->allowedModels()[$model] ?? [];

        return is_array($capabilities) ? $capabilities : [];
    }

    /**
     * Whether a model declares a capability.
     */
    public function supports(?string $model, string $capability): bool
    {
        return (bool) ($this->modelCapabilities($model)[$capability] ?? false);
    }

    /**
     * The required capabilities a model does not declare.
     *
     * @param  list<string>  $required
     * @return list<string>
     */
    public function missingCapabilities(?string $model, array $required): array
    {
        return array_values(array_filter(
            $required,
            fn (string $capability): bool => ! $this->supports($model, $capability),
        ));
    }

    /**
     * Default timeout in seconds.
     */
    public function timeout(): int
    {
        return max(1, (int) config('lnx_ai.timeout', 30));
    }

    /**
     * Default maximum output tokens for a single request.
     */
    public function maxOutputTokens(): int
    {
        return max(1, (int) config('lnx_ai.max_output_tokens', 1024));
    }

    /**
     * The per-user quota, normalized to a safe shape.
     *
     * @return array{window: string, requests: int, tokens: int}
     */
    public function userLimit(): array
    {
        return $this->normalizeLimit(config('lnx_ai.limits.user', []));
    }

    /**
     * The quota for a feature, falling back to the default limit.
     *
     * @return array{window: string, requests: int, tokens: int}
     */
    public function applicationLimit(?string $application): array
    {
        $application = $this->normalizeApplication($application);

        $limits = config('lnx_ai.limits.applications', []);
        $limits = is_array($limits) ? $limits : [];

        $limit = $limits[$application] ?? config('lnx_ai.limits.default', []);

        return $this->normalizeLimit($limit);
    }

    /**
     * Normalize an application key, using the default when none is given.
     */
    public function normalizeApplication(?string $application): string
    {
        $application = is_string($application) ? trim($application) : '';

        return $application !== '' ? $application : self::DEFAULT_APPLICATION;
    }

    /**
     * Normalize a raw limit definition into valid window, requests and tokens.
     *
     * @return array{window: string, requests: int, tokens: int}
     */
    protected function normalizeLimit(mixed $limit): array
    {
        $limit = is_array($limit) ? $limit : [];

        $window = $limit['window'] ?? 'hour';
        $window = is_string($window) && in_array($window, self::WINDOWS, true) ? $window : 'hour';

        return [
            'window' => $window,
            'requests' => max(0, (int) ($limit['requests'] ?? 0)),
            'tokens' => max(0, (int) ($limit['tokens'] ?? 0)),
        ];
    }

    /**
     * The declared cost rate for a model, or null when none is configured.
     *
     * @return array<string, mixed>|null
     */
    public function pricingFor(?string $model): ?array
    {
        if ($model === null) {
            return null;
        }

        $models = config('lnx_ai.pricing.models', []);
        $models = is_array($models) ? $models : [];

        $json = config('lnx_ai.pricing.models_json');

        if (is_string($json) && trim($json) !== '') {
            $decoded = json_decode($json, true);

            if (is_array($decoded)) {
                $models = array_replace($models, $decoded);
            }
        }

        $rate = $models[$model] ?? null;

        return is_array($rate) ? $rate : null;
    }

    /**
     * Estimate a cost from configured rates, or null when none apply.
     *
     * @return array{amount: float, currency: string, version: string|null}|null
     */
    public function estimateCost(?string $model, int $inputTokens, int $outputTokens): ?array
    {
        $rate = $this->pricingFor($model);

        if ($rate === null) {
            return null;
        }

        $inputRate = $rate['input_per_million'] ?? null;
        $outputRate = $rate['output_per_million'] ?? null;

        if (! is_numeric($inputRate) || ! is_numeric($outputRate)) {
            return null;
        }

        $amount = ($inputTokens / 1_000_000) * (float) $inputRate
            + ($outputTokens / 1_000_000) * (float) $outputRate;

        return [
            'amount' => round($amount, 6),
            'currency' => (string) config('lnx_ai.pricing.currency', 'USD'),
            'version' => $this->pricingVersion(),
        ];
    }

    /**
     * The configured currency used to display estimated costs.
     */
    public function pricingCurrency(): string
    {
        $currency = config('lnx_ai.pricing.currency', 'USD');

        return is_string($currency) && trim($currency) !== '' ? trim($currency) : 'USD';
    }

    /**
     * The configured pricing version, falling back to its updated date.
     */
    public function pricingVersion(): ?string
    {
        $version = config('lnx_ai.pricing.version');

        if (is_string($version) && trim($version) !== '') {
            return trim($version);
        }

        $updatedAt = config('lnx_ai.pricing.updated_at');

        return is_string($updatedAt) && trim($updatedAt) !== '' ? trim($updatedAt) : null;
    }

    /**
     * Whether AI is enabled, credentialed, and pointed at an allowed model.
     */
    public function isReady(?string $model = null): bool
    {
        $model ??= $this->defaultModel();

        return $this->enabled()
            && $this->providerConfigured()
            && $this->isModelAllowed($model);
    }

    /**
     * Machine-readable configuration problems, in a stable order.
     *
     * @return list<string>
     */
    public function issues(): array
    {
        $issues = [];

        if (! $this->enabled()) {
            $issues[] = 'disabled';
        }

        if (! $this->apiKeyPresent()) {
            $issues[] = 'missing_api_key';
        } elseif (! $this->providerConfigured()) {
            $issues[] = 'provider_not_configured';
        }

        $model = $this->defaultModel();

        if ($model === null) {
            $issues[] = 'missing_model';
        } elseif (! $this->isModelAllowed($model)) {
            $issues[] = 'model_not_allowed';
        }

        return array_values(array_unique($issues));
    }

    /**
     * A status summary safe to render for root.
     *
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $allowedModels = [];

        foreach ($this->allowedModels() as $id => $capabilities) {
            $allowedModels[] = [
                'id' => (string) $id,
                'capabilities' => is_array($capabilities) ? $capabilities : [],
            ];
        }

        return [
            'enabled' => $this->enabled(),
            'ready' => $this->isReady(),
            'provider' => $this->providerName(),
            'default_model' => $this->defaultModel(),
            'api_key_set' => $this->apiKeyPresent(),
            'timeout' => $this->timeout(),
            'max_output_tokens' => $this->maxOutputTokens(),
            'user_limit' => $this->userLimit(),
            'application_limits' => $this->applicationLimits(),
            'pricing_version' => $this->pricingVersion(),
            'allowed_models' => $allowedModels,
            'issues' => $this->issues(),
        ];
    }

    /**
     * Every configured application limit, keyed by application name.
     *
     * @return array<string, array{window: string, requests: int, tokens: int}>
     */
    public function applicationLimits(): array
    {
        $limits = config('lnx_ai.limits.applications', []);
        $limits = is_array($limits) ? $limits : [];

        $normalized = [];

        foreach ($limits as $application => $limit) {
            if (is_string($application) && $application !== '') {
                $normalized[$application] = $this->normalizeLimit($limit);
            }
        }

        return $normalized;
    }
}
