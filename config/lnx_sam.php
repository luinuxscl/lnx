<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable the platform agent
    |--------------------------------------------------------------------------
    |
    | The runtime stays off until it is explicitly enabled. While disabled, no
    | contributor is exposed, the panel is not published and no billable
    | request is performed.
    |
    */

    'enabled' => (bool) env('SAM_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Surface permission
    |--------------------------------------------------------------------------
    |
    | Permission that protects the agent surface. The product package that
    | ships the identity declares it; the runtime only checks it. Every module
    | tool re-checks its own permissions and record scope.
    |
    */

    'permission' => (string) env('SAM_PERMISSION', 'sam.use'),

    /*
    |--------------------------------------------------------------------------
    | Mode ceiling
    |--------------------------------------------------------------------------
    |
    | Highest mode any identity may reach in this deployment: `read_only` lets
    | Sam read and explain but not propose, `draft` lets it propose drafts and
    | `assisted` lets the owning module execute reversible actions after
    | confirmation. A role may lower the ceiling with a `sam_mode_ceilings`
    | row; the effective mode is always the lowest of both, root included.
    |
    */

    'max_mode' => (string) env('SAM_MAX_MODE', 'read_only'),

    /*
    |--------------------------------------------------------------------------
    | AI application
    |--------------------------------------------------------------------------
    |
    | Application name used for the quota and the usage record of every turn.
    | The package declares its quota with the kit's AI application registry;
    | without a declaration the deployment default applies.
    |
    */

    'application' => (string) env('SAM_APPLICATION', 'sam.turn'),

    /*
    |--------------------------------------------------------------------------
    | Brief narrative
    |--------------------------------------------------------------------------
    |
    | The brief keeps its deterministic fallback and may receive a model
    | narrative. That call uses its own quota bucket, a short timeout and a
    | session cache, so it never consumes the chat turn or blocks the panel.
    |
    */

    'brief_application' => (string) env('SAM_BRIEF_APPLICATION', 'sam.brief'),

    'brief_timeout' => (int) env('SAM_BRIEF_TIMEOUT', 4),

    'brief_max_tokens' => (int) env('SAM_BRIEF_MAX_TOKENS', 900),

    'brief_cache_ttl' => (int) env('SAM_BRIEF_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Ambient proactive cache
    |--------------------------------------------------------------------------
    |
    | Short-lived cache for batched row signals used by the ambient badge. Keys
    | include the actor, module source, resource, record id and `updated_at`.
    |
    */

    'ambient_cache_ttl' => (int) env('SAM_AMBIENT_CACHE_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | List filter analysis
    |--------------------------------------------------------------------------
    |
    | Aggregate proactive counts for the current list filters. Rows beyond the
    | cap are skipped and the analysis is marked truncated.
    |
    */

    'filter_max_rows' => (int) env('SAM_FILTER_MAX_ROWS', 5000),

    'filter_cache_ttl' => (int) env('SAM_FILTER_CACHE_TTL', 120),

    /*
    |--------------------------------------------------------------------------
    | Turn bounds
    |--------------------------------------------------------------------------
    |
    | Maximum characters of one input, previous turns sent as context, and the
    | output cap and timeout of the agent. Input and context are bounded so a
    | single turn stays cheap and predictable.
    |
    */

    'max_input_chars' => (int) env('SAM_MAX_INPUT_CHARS', 2000),

    'max_context_messages' => (int) env('SAM_MAX_CONTEXT_MESSAGES', 20),

    'max_output_tokens' => (int) env('SAM_MAX_OUTPUT_TOKENS', 1200),

    'timeout' => (int) env('SAM_TIMEOUT', 45),

    /*
    |--------------------------------------------------------------------------
    | Conversation title
    |--------------------------------------------------------------------------
    |
    | Characters kept in the deterministic title of a new thread, derived from
    | its first message without calling the model.
    |
    */

    'title_chars' => (int) env('SAM_TITLE_CHARS', 60),

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Days the conversation history is kept. `0` keeps it indefinitely. Older
    | messages are pruned by the `sam:prune-conversations` command, scheduled
    | daily.
    |
    */

    'retention_days' => (int) env('SAM_RETENTION_DAYS', 0),

    /*
    |--------------------------------------------------------------------------
    | Badge metrics ledger
    |--------------------------------------------------------------------------
    |
    | Directory of the JSONL ledger that keeps the aggregated badge metrics
    | alive when the schema is rebuilt. The events live in the database; the
    | export appends one bucket here before the demo reset wipes them.
    |
    */

    'metrics_path' => env('SAM_METRICS_PATH'),

];
