<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApplySiteSettings
{
    /**
     * Apply the persisted site settings to the current request.
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

        if (filled($settings->site_title)) {
            config([
                'app.name' => $settings->site_title,
                'mail.from.name' => $settings->site_title,
            ]);
        }

        $timezone = $settings->siteTimezone();
        config([
            'app.locale' => $settings->siteLocale(),
            'app.timezone' => $timezone,
        ]);

        View::share('site', $settings);

        return $next($request);
    }
}
