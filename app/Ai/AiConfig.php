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
            'allowed_models' => $allowedModels,
            'issues' => $this->issues(),
        ];
    }
}
