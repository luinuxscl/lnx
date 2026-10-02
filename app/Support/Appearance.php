<?php

namespace App\Support;

use App\Models\User;
use DateTimeZone;
use Illuminate\Support\Facades\Auth;

class Appearance
{
    /**
     * The accent themes supported by the application.
     *
     * @var list<string>
     */
    public const THEMES = ['orange', 'teal', 'rose', 'violet', 'blue', 'emerald'];

    /**
     * The color schemes supported by the application.
     *
     * @var list<string>
     */
    public const COLOR_SCHEMES = ['light', 'dark', 'system'];

    /**
     * The theme used when the user has no stored preference.
     */
    public static function defaultTheme(): string
    {
        return self::THEMES[0];
    }

    /**
     * The color scheme used when the user has no stored preference.
     */
    public static function defaultColorScheme(): string
    {
        return 'system';
    }

    /**
     * All valid time zone identifiers.
     *
     * @return list<string>
     */
    public static function timezones(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    /**
     * Whether the given value is a valid time zone identifier.
     */
    public static function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, self::timezones(), true);
    }

    /**
     * The time zone used to present dates for the current request.
     *
     * Authenticated users with a stored preference use their own zone;
     * everyone else keeps the application default.
     */
    public static function currentTimezone(?User $user = null): string
    {
        $user ??= Auth::user();

        $timezone = $user?->timezone;

        return is_string($timezone) && self::isValidTimezone($timezone)
            ? $timezone
            : (string) config('app.timezone');
    }
}
