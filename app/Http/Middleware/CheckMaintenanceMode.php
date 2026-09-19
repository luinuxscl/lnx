<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckMaintenanceMode
{
    /**
     * Block requests while the application is in maintenance mode.
     *
     * Root users and authentication routes stay reachable so a superuser can
     * always sign in and disable maintenance mode.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $settings = Setting::current();
        } catch (Throwable) {
            return $next($request);
        }

        if (! $settings->maintenance_mode) {
            return $next($request);
        }

        if ($request->user()?->hasRole(Role::Root)) {
            return $next($request);
        }

        if ($request->routeIs(
            'login',
            'logout',
            'register',
            'password.*',
            'verification.*',
            'two-factor.*',
            'passkey.*',
            'well-known.*',
            'locale.*',
        )) {
            return $next($request);
        }

        return response()
            ->view('errors.maintenance', [
                'message' => filled($settings->maintenance_message)
                    ? $settings->maintenance_message
                    : __('We are performing scheduled maintenance. Please check back soon.'),
            ], 503)
            ->header('Retry-After', '3600');
    }
}
