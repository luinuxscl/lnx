<?php

namespace App\Support;

use App\Models\Setting;

class PublicConfiguration
{
    /**
     * Build the explicit, non-sensitive configuration surface.
     *
     * Only whitelisted fields are exposed. Secrets, credentials and full
     * environment values are never included.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $settings = Setting::current();

        return [
            'application' => [
                'name' => $settings->title(),
                'version' => (string) config('app.version'),
            ],
            'localization' => [
                'locale' => (string) config('app.locale'),
                'fallback_locale' => (string) config('app.fallback_locale'),
                'available_locales' => array_keys((array) config('app.available_locales')),
                'timezone' => (string) config('app.timezone'),
            ],
            'site' => [
                'title' => $settings->title(),
                'tagline' => $settings->tagline(),
                'meta_description' => $settings->description(),
                'footer_text' => $settings->footer(),
                'registration_mode' => $settings->registration_mode,
                'maintenance_mode' => $settings->maintenance_mode,
            ],
        ];
    }
}
