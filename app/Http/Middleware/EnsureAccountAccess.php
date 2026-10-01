<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->hasAccess($request)) {
            return $this->reject($request);
        }

        $response = $next($request);

        if (! $this->hasAccess($request)) {
            return $this->reject($request);
        }

        return $response;
    }

    private function hasAccess(Request $request): bool
    {
        $guard = Auth::guard(config('fortify.guard'));
        $user = $guard->user();

        if ($user !== null) {
            return $this->matches($user->getKey(), $request->session()->get('auth_access.web'));
        }

        $challengedId = $request->session()->get('login.id');

        return $challengedId === null || $this->matches($challengedId, $request->session()->get('login.auth_access'));
    }

    private function matches(int|string $id, mixed $marker): bool
    {
        $current = User::query()->find($id);

        if ($current === null || $current->isSuspended()) {
            return false;
        }

        if ($marker === null) {
            return $current->auth_version === 0;
        }

        return is_array($marker)
            && (is_int($marker['id'] ?? null) || is_string($marker['id'] ?? null))
            && (string) $marker['id'] === (string) $id
            && ($marker['version'] ?? null) === $current->auth_version;
    }

    private function reject(Request $request): Response
    {
        $locale = $request->session()->get('locale');
        $guard = Auth::guard(config('fortify.guard'));
        $recaller = $guard->getRecallerName();

        $guard->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($locale !== null) {
            $request->session()->put('locale', $locale);
        }

        $response = $request->expectsJson()
            ? response()->json(['message' => __('Your session is no longer valid. Please sign in again.')], 401)
            : redirect()->route('login')->with('status', __('Your session is no longer valid. Please sign in again.'));

        return $response->withoutCookie($recaller);
    }
}
