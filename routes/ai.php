<?php

use App\Http\Middleware\AuthenticateMcpToken;
use App\Http\Middleware\EnsureMcpServerEnabled;
use App\Http\Middleware\RecordMcpCorrelation;
use App\Mcp\Servers\LnxServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP Servers
|--------------------------------------------------------------------------
|
| The application MCP server is a read-only pilot. It stays disabled until
| explicitly enabled: while disabled every request returns a 404. Every
| enabled request must present a valid, revocable Bearer token tied to a user
| identity. See README.md ("Servidor MCP (lectura)").
|
*/

Mcp::web(config('mcp_server.path'), LnxServer::class)
    ->middleware([
        EnsureMcpServerEnabled::class,
        RecordMcpCorrelation::class,
        AuthenticateMcpToken::class,
    ]);
