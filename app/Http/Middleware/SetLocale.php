<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Set the application locale for the current request.
     *
     * Authenticated users follow the locale stored on their account, so their
     * choice travels across devices. Guests keep the session preference.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys((array) config('app.available_locales'));

        $candidates = [
            $request->user()?->locale,
            $request->session()->get('locale'),
        ];

        $locale = collect($candidates)
            ->first(fn ($candidate) => is_string($candidate) && in_array($candidate, $available, true));

        App::setLocale($locale ?? config('app.locale'));

        return $next($request);
    }
}
