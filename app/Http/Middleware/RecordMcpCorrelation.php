<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RecordMcpCorrelation
{
    /**
     * Attach a correlation identifier to every MCP request.
     *
     * The identifier is reused by the audit log and echoed back in the
     * X-Correlation-Id response header.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = (string) Str::uuid();

        $request->attributes->set('mcp.correlation_id', $correlationId);

        $response = $next($request);
        $response->headers->set('X-Correlation-Id', $correlationId);

        return $response;
    }
}
