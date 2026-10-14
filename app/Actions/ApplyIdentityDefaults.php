<?php

namespace App\Actions;

use App\Models\Setting;
use InvalidArgumentException;

/**
 * Persists the identity defaults contributed by the registered identity
 * seeders, applying the precedence rules of the module contract:
 *
 * - A value already stored in the settings row always wins (root's manual
 *   configuration, or a seeder that ran with a higher priority).
 * - A value that equals the effective kit fallback is not persisted, so an
 *   installation without a customization package keeps the current behavior
 *   (no settings row is created and the identity keeps coming from config).
 * - Only undefined values are filled.
 *
 * Running it twice changes nothing.
 */
class ApplyIdentityDefaults
{
    /**
     * Identity settings columns a seeder may seed, with their kit fallback.
     *
     * @return array<string, string|null>
     */
    protected function fallbacks(): array
    {
        return [
            'site_title' => config('app.name'),
            'site_tagline' => config('app.tagline'),
            'footer_text' => config('app.footer_text'),
            'meta_description' => null,
            'logo_path' => Setting::DEFAULT_LOGO,
            'logo_dark_path' => null,
            'favicon_path' => Setting::DEFAULT_FAVICON,
            'apple_touch_icon_path' => Setting::DEFAULT_APPLE_TOUCH_ICON,
            'locale' => config('app.locale'),
            'timezone' => config('app.timezone'),
        ];
    }

    /**
     * Fill the settings row with the given identity defaults.
     *
     * @param  array<string, string|null>  $defaults
     *
     * @throws InvalidArgumentException when a field is not part of the identity
     */
    public function handle(array $defaults): void
    {
        $fallbacks = $this->fallbacks();

        foreach (array_keys($defaults) as $field) {
            if (! array_key_exists($field, $fallbacks)) {
                throw new InvalidArgumentException("Unknown identity field [{$field}].");
            }
        }

        $settings = Setting::query()->first() ?? new Setting;
        $dirty = false;

        foreach ($defaults as $field => $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }

            if ($settings->{$field} !== null) {
                continue;
            }

            if ($value === $fallbacks[$field]) {
                continue;
            }

            $settings->{$field} = $value;
            $dirty = true;
        }

        if (! $dirty) {
            return;
        }

        $settings->save();
        Setting::forgetCache();
    }
}
