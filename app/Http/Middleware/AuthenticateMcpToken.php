<?php

namespace App\Http\Middleware;

use App\Actions\RecordMcpInvocation;
use App\Actions\ResolveMcpToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMcpToken
{
    public function __construct(
        private ResolveMcpToken $resolveToken,
        private RecordMcpInvocation $recorder,
    ) {}

    /**
     * Authenticate the MCP request through a revocable Bearer token.
     *
     * The token is resolved to a user identity, rejected when revoked, expired
     * or suspended, and the request is rate limited per identity.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $this->correlationId($request);
        $plainText = $request->bearerToken();

        $token = $this->resolveToken->handle($plainText);

        if ($token === null || ! $token->isActive()) {
            $this->recorder->handle(null, 'connection', 'unauthenticated', $correlationId);

            return $this->unauthenticated();
        }

        $user = $token->user;

        if (! $user instanceof User) {
            $this->recorder->handle(null, 'connection', 'unauthenticated', $correlationId);

            return $this->unauthenticated();
        }

        if ($user->isSuspended()) {
            $this->recorder->handle($user, 'connection', 'denied', $correlationId);

            return $this->forbidden();
        }

        $limit = (int) config('mcp_server.rate_limit.per_minute');
        $key = 'mcp:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $this->recorder->handle($user, 'connection', 'denied', $correlationId);

            return $this->tooManyRequests(RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, 60);

        $token->forceFill(['last_used_at' => now()])->save();

        Auth::guard(config('fortify.guard'))->setUser($user);
        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }

    private function correlationId(Request $request): string
    {
        $correlationId = $request->attributes->get('mcp.correlation_id');

        return is_string($correlationId) ? $correlationId : (string) Str::uuid();
    }

    private function unauthenticated(): Response
    {
        return response()->json(['error' => 'Unauthenticated.'], 401);
    }

    private function forbidden(): Response
    {
        return response()->json(['error' => 'Account suspended.'], 403);
    }

    private function tooManyRequests(int $retryAfter): Response
    {
        return response()
            ->json(['error' => 'Too many requests.'], 429)
            ->header('Retry-After', (string) $retryAfter);
    }
}
