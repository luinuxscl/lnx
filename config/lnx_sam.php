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

];
