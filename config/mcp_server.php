<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable the read-only application MCP server
    |--------------------------------------------------------------------------
    |
    | The server stays disabled until explicitly configured. When disabled, the
    | MCP route is not registered at all, so no client can reach it and no
    | credentials can be used. Enable it per environment, never by default.
    |
    */

    'enabled' => (bool) env('MCP_SERVER_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP path
    |--------------------------------------------------------------------------
    |
    | The path the web MCP transport is served from. The resulting route only
    | accepts authenticated POST requests with a Bearer token.
    |
    */

    'path' => (string) env('MCP_SERVER_PATH', 'mcp'),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default and maximum page sizes for list tools. Requests asking for more
    | than the maximum are capped, never rejected.
    |
    */

    'pagination' => [
        'per_page' => (int) env('MCP_SERVER_PER_PAGE', 10),
        'max_per_page' => (int) env('MCP_SERVER_MAX_PER_PAGE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    |
    | Maximum number of MCP requests an authenticated identity may make per
    | minute. Exceeding it returns a 429 response with a Retry-After header.
    |
    */

    'rate_limit' => [
        'per_minute' => (int) env('MCP_SERVER_RATE_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Write operations
    |--------------------------------------------------------------------------
    |
    | The server is read-only by default. Writing is an explicit, deployment
    | level decision: while disabled no write tool is registered or announced,
    | even if a package declares it. When enabled, writes are rate limited
    | separately from reads.
    |
    */

    'writes' => [
        'enabled' => (bool) env('MCP_SERVER_ALLOW_WRITES', false),
        'rate_limit_per_minute' => (int) env('MCP_SERVER_WRITE_RATE_LIMIT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit retention
    |--------------------------------------------------------------------------
    |
    | Number of days tool invocations and write operations are retained. Older
    | rows are removed by the mcp:prune-invocations and mcp:prune-writes
    | commands, which are scheduled daily. The diff of a write is the exact
    | change applied (field, before, after); it stays in the database and is
    | never written to application logs. It can be turned off per deployment.
    |
    */

    'audit' => [
        'retention_days' => (int) env('MCP_SERVER_AUDIT_RETENTION_DAYS', 90),
        'diff' => (bool) env('MCP_SERVER_AUDIT_DIFF', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token lifetime
    |--------------------------------------------------------------------------
    |
    | Optional number of days a newly created access token remains valid. When
    | null, tokens do not expire on their own and must be revoked manually.
    |
    */

    'token' => [
        'expires_days' => env('MCP_SERVER_TOKEN_EXPIRES_DAYS') === null
            ? null
            : (int) env('MCP_SERVER_TOKEN_EXPIRES_DAYS'),
    ],

];
