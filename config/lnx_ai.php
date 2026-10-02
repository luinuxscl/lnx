<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable AI features
    |--------------------------------------------------------------------------
    |
    | AI stays disabled until it is explicitly enabled. While disabled, no
    | feature performs a billable request and no provider credential is
    | required. Enable it per environment, never by default.
    |
    */

    'enabled' => (bool) env('AI_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Provider
    |--------------------------------------------------------------------------
    |
    | The Laravel AI SDK provider used for every request. OpenRouter is the
    | default and the only one lnx targets. The provider key lives in the SDK
    | config (`config/ai.php`), read from OPENROUTER_API_KEY.
    |
    */

    'provider' => (string) env('AI_PROVIDER', 'openrouter'),

    /*
    |--------------------------------------------------------------------------
    | Default model
    |--------------------------------------------------------------------------
    |
    | The model identifier requested for new features. It must appear in the
    | allowlist below; no commercial model is pinned in the code. Leave it
    | empty to keep features disabled.
    |
    */

    'default_model' => env('AI_MODEL'),

    /*
    |--------------------------------------------------------------------------
    | Allowed models and their capabilities
    |--------------------------------------------------------------------------
    |
    | Server-side allowlist. A model outside this list is rejected before any
    | provider call. Capabilities are declared explicitly because not every
    | model supports tools, structured output or streaming. `context` is the
    | maximum input size in tokens. Add the models a deployment actually uses.
    |
    | Example:
    | 'vendor/model-id' => [
    |     'tools' => false,
    |     'structured' => false,
    |     'streaming' => true,
    |     'context' => 128000,
    | ],
    |
    */

    'allowed_models' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment allowlist
    |--------------------------------------------------------------------------
    |
    | Optional JSON object merged over the allowlist above, so a deployment can
    | declare its models without editing this file. Format:
    | {"vendor/model-id":{"tools":false,"structured":false,"streaming":true,"context":128000}}
    |
    */

    'allowed_models_json' => env('AI_ALLOWED_MODELS'),

    /*
    |--------------------------------------------------------------------------
    | Request limits
    |--------------------------------------------------------------------------
    |
    | Default timeout in seconds and maximum output tokens for a single
    | request, used when a feature does not override them.
    |
    */

    'timeout' => (int) env('AI_TIMEOUT', 30),

    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 1024),

    /*
    |--------------------------------------------------------------------------
    | Connection test
    |--------------------------------------------------------------------------
    |
    | Limits for the explicit root connection test. It is never triggered by
    | opening a page: it runs only when root presses the button. Output is
    | capped to keep the probe cheap, and it is rate limited per root user.
    |
    */

    'connection_test' => [
        'timeout' => (int) env('AI_CONNECTION_TEST_TIMEOUT', 15),
        'max_output_tokens' => (int) env('AI_CONNECTION_TEST_MAX_OUTPUT_TOKENS', 16),
        'per_minute' => (int) env('AI_CONNECTION_TEST_RATE_LIMIT', 5),
    ],

];
