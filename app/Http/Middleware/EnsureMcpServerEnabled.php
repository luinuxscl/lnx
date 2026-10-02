<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMcpServerEnabled
{
    /**
     * Reject MCP requests while the server is not explicitly enabled.
     *
     * The route is registered unconditionally so operators can toggle the
     * server with configuration; while disabled the endpoint is invisible
     * (404) and no credential can be used.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) config('mcp_server.enabled'), 404);

        return $next($request);
    }
}
