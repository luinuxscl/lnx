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
    | model supports tools, structured output, streaming or images. `context`
    | is the maximum input size in tokens and `vision` declares that the model
    | accepts image/file attachments. Add the models a deployment uses.
    |
    | Example:
    | 'vendor/model-id' => [
    |     'tools' => false,
    |     'structured' => false,
    |     'streaming' => true,
    |     'context' => 128000,
    |     'vision' => true,
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

    /*
    |--------------------------------------------------------------------------
    | Text assistant
    |--------------------------------------------------------------------------
    |
    | Limits for the reusable text assistant (improve, summarize, translate).
    | Input size, output size and timeout are capped independently so a single
    | request stays bounded. Only the explicitly provided text is sent; the
    | component never forwards other form fields. The demo screen is opt-in.
    |
    */

    'text_assistant' => [
        'timeout' => (int) env('AI_TEXT_ASSISTANT_TIMEOUT', 30),
        'max_output_tokens' => (int) env('AI_TEXT_ASSISTANT_MAX_OUTPUT_TOKENS', 1200),
        'max_input_chars' => (int) env('AI_TEXT_ASSISTANT_MAX_INPUT_CHARS', 8000),
        'languages' => ['es', 'en'],
        'demo_enabled' => (bool) env('AI_TEXT_ASSISTANT_DEMO_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Interactive Chat
    |--------------------------------------------------------------------------
    |
    | Configuration for the interactive chat feature.
    |
    */

    'chat' => [
        'timeout' => (int) env('AI_CHAT_TIMEOUT', 60),
        'max_output_tokens' => (int) env('AI_CHAT_MAX_OUTPUT_TOKENS', 2048),
        'max_input_chars' => (int) env('AI_CHAT_MAX_INPUT_CHARS', 4000),
        'max_context_messages' => (int) env('AI_CHAT_MAX_CONTEXT_MESSAGES', 20),
        'retention_days' => (int) env('AI_CHAT_RETENTION_DAYS', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Extraction
    |--------------------------------------------------------------------------
    |
    | Configuration for document data extraction.
    |
    */

    'document_extraction' => [
        'timeout' => (int) env('AI_DOCUMENT_TIMEOUT', 60),
        'max_output_tokens' => (int) env('AI_DOCUMENT_MAX_OUTPUT_TOKENS', 2048),
        'max_file_size_kb' => (int) env('AI_DOCUMENT_MAX_FILE_SIZE_KB', 10240),
        'max_pages' => (int) env('AI_DOCUMENT_MAX_PAGES', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Knowledge Base (RAG)
    |--------------------------------------------------------------------------
    |
    | Configuration for internal knowledge retrieval-augmented generation.
    |
    */

    'knowledge' => [
        'enabled' => (bool) env('AI_KNOWLEDGE_ENABLED', false),
        'embedding_model' => env('AI_KNOWLEDGE_EMBEDDING_MODEL', 'google/gemini-embedding-001'),
        'chunk_size' => (int) env('AI_KNOWLEDGE_CHUNK_SIZE', 500),
        'chunk_overlap' => (int) env('AI_KNOWLEDGE_CHUNK_OVERLAP', 50),
        'max_chunks' => (int) env('AI_KNOWLEDGE_MAX_CHUNKS', 5),
        'min_similarity' => (float) env('AI_KNOWLEDGE_MIN_SIMILARITY', 0.2),
        'storage' => env('AI_KNOWLEDGE_STORAGE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage limits
    |--------------------------------------------------------------------------
    |
    | Quotas enforced before any provider call. Every request must satisfy
    | both dimensions: the per-user limit and the per-application limit. Each
    | limit declares a sliding-window unit (minute, hour or day) and a maximum
    | number of requests and tokens inside that window.
    |
    | "applications" are the AI features of the kit (for example
    | connection_test or a future text_assistant). A feature without an
    | explicit entry falls back to "default". A limit of 0 disables that
    | dimension. No limit is pinned in code: a deployment declares its own.
    |
    | Tokens are reserved before the call using the maximum output size, then
    | reconciled with the actual usage once the provider reports it, so
    | concurrent requests cannot slip past the limit.
    |
    */

    'limits' => [
        'user' => [
            'window' => (string) env('AI_USER_LIMIT_WINDOW', 'hour'),
            'requests' => (int) env('AI_USER_LIMIT_REQUESTS', 60),
            'tokens' => (int) env('AI_USER_LIMIT_TOKENS', 200000),
        ],

        'default' => [
            'window' => (string) env('AI_DEFAULT_LIMIT_WINDOW', 'hour'),
            'requests' => (int) env('AI_DEFAULT_LIMIT_REQUESTS', 60),
            'tokens' => (int) env('AI_DEFAULT_LIMIT_TOKENS', 200000),
        ],

        'applications' => [
            'connection_test' => [
                'window' => (string) env('AI_CONNECTION_TEST_LIMIT_WINDOW', 'hour'),
                'requests' => (int) env('AI_CONNECTION_TEST_LIMIT_REQUESTS', 5),
                'tokens' => (int) env('AI_CONNECTION_TEST_LIMIT_TOKENS', 2000),
            ],

            'text_assistant' => [
                'window' => (string) env('AI_TEXT_ASSISTANT_LIMIT_WINDOW', 'hour'),
                'requests' => (int) env('AI_TEXT_ASSISTANT_LIMIT_REQUESTS', 30),
                'tokens' => (int) env('AI_TEXT_ASSISTANT_LIMIT_TOKENS', 100000),
            ],

            'chat' => [
                'window' => (string) env('AI_CHAT_LIMIT_WINDOW', 'hour'),
                'requests' => (int) env('AI_CHAT_LIMIT_REQUESTS', 40),
                'tokens' => (int) env('AI_CHAT_LIMIT_TOKENS', 150000),
            ],

            'document_extraction' => [
                'window' => (string) env('AI_DOCUMENT_LIMIT_WINDOW', 'hour'),
                'requests' => (int) env('AI_DOCUMENT_LIMIT_REQUESTS', 20),
                'tokens' => (int) env('AI_DOCUMENT_LIMIT_TOKENS', 100000),
            ],

            'knowledge_indexing' => [
                'window' => (string) env('AI_KNOWLEDGE_INDEXING_LIMIT_WINDOW', 'hour'),
                'requests' => (int) env('AI_KNOWLEDGE_INDEXING_LIMIT_REQUESTS', 30),
                'tokens' => (int) env('AI_KNOWLEDGE_INDEXING_LIMIT_TOKENS', 150000),
            ],

            'knowledge_query' => [
                'window' => (string) env('AI_KNOWLEDGE_QUERY_LIMIT_WINDOW', 'hour'),
                'requests' => (int) env('AI_KNOWLEDGE_QUERY_LIMIT_REQUESTS', 60),
                'tokens' => (int) env('AI_KNOWLEDGE_QUERY_LIMIT_TOKENS', 150000),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost estimation
    |--------------------------------------------------------------------------
    |
    | Optional rates used only to estimate a cost when the provider does not
    | report one. Rates are never pinned in code: a deployment declares them
    | (by environment or in the array below) together with a version/date and
    | a currency, and both are recorded on every estimated row. A missing rate
    | means the cost stays unknown, never zero.
    |
    | Format (rates are per one million tokens):
    | 'vendor/model-id' => ['input_per_million' => 3, 'output_per_million' => 15],
    |
    */

    'pricing' => [
        'version' => env('AI_PRICING_VERSION'),
        'updated_at' => env('AI_PRICING_UPDATED_AT'),
        'currency' => (string) env('AI_PRICING_CURRENCY', 'USD'),
        'models' => [
            //
        ],
        'models_json' => env('AI_PRICING_MODELS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage record retention
    |--------------------------------------------------------------------------
    |
    | Number of days AI consumption records are retained. Older rows are
    | removed by the ai:prune-usage command, scheduled daily. Set 0 to keep
    | records indefinitely.
    |
    */

    'usage' => [
        'retention_days' => (int) env('AI_USAGE_RETENTION_DAYS', 90),
    ],

];
