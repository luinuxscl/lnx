<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string|null $site_title
 * @property string|null $site_tagline
 * @property string|null $meta_description
 * @property string|null $admin_email
 * @property string|null $logo_path
 * @property string|null $logo_dark_path
 * @property string|null $favicon_path
 * @property string|null $apple_touch_icon_path
 * @property string|null $footer_text
 * @property string|null $locale
 * @property string|null $timezone
 * @property string|null $analytics_id
 * @property bool $maintenance_mode
 * @property string|null $maintenance_message
 * @property bool $home_enabled
 * @property Carbon|null $test_email_sent_at
 * @property string|null $test_email_sent_to
 * @property Carbon|null $test_email_failed_at
 */
#[Fillable([
    'site_title',
    'site_tagline',
    'meta_description',
    'admin_email',
    'logo_path',
    'logo_dark_path',
    'favicon_path',
    'apple_touch_icon_path',
    'footer_text',
    'locale',
    'timezone',
    'analytics_id',
    'maintenance_mode',
    'maintenance_message',
    'registration_mode',
    'home_enabled',
    'test_email_sent_at',
    'test_email_sent_to',
    'test_email_failed_at',
])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected $attributes = ['registration_mode' => 'open', 'home_enabled' => true];

    public static function registrationIsOpen(): bool
    {
        return self::current()->registration_mode !== 'invitation_only';
    }

    /**
     * Whether the public landing page is served at the home route.
     */
    public static function homeIsEnabled(): bool
    {
        return (bool) self::current()->home_enabled;
    }

    /**
     * The cache key used to store the singleton settings row.
     */
    public const CACHE_KEY = 'settings';

    /**
     * Default branding assets shipped with the kit, relative to the public root.
     */
    public const DEFAULT_LOGO = 'branding/lnx-logo.png';

    public const DEFAULT_FAVICON = 'favicon.png';

    public const DEFAULT_APPLE_TOUCH_ICON = 'apple-touch-icon.png';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'maintenance_mode' => 'boolean',
            'home_enabled' => 'boolean',
            'test_email_sent_at' => 'datetime',
            'test_email_failed_at' => 'datetime',
        ];
    }

    /**
     * Get the singleton settings instance.
     *
     * The row is cached as a plain attribute array (never as a serialized
     * model) and hydrated on read, so the cache stays safe across deployments.
     * Falls back to an empty instance so every accessor can apply its own
     * default without requiring a row to exist.
     */
    public static function current(): self
    {
        /** @var array<string, mixed> $attributes */
        $attributes = Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => self::query()->first()?->getAttributes() ?? [],
        );

        $settings = new self;
        $settings->forceFill($attributes);
        $settings->exists = array_key_exists('id', $attributes);

        return $settings;
    }

    /**
     * Forget the cached settings instance.
     */
    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get the site title, falling back to the application name.
     */
    public function title(): string
    {
        return $this->site_title ?: (string) config('app.name', 'LNX');
    }

    /**
     * Get the site tagline, falling back to the configured kit default.
     */
    public function tagline(): ?string
    {
        return $this->site_tagline ?: (config('app.tagline') ?: null);
    }

    /**
     * Get the footer text, falling back to the configured kit default.
     */
    public function footer(): ?string
    {
        return $this->footer_text ?: (config('app.footer_text') ?: null);
    }

    /**
     * Get the meta description, falling back to the tagline.
     */
    public function description(): ?string
    {
        return $this->meta_description ?: $this->tagline();
    }

    /**
     * Get the site-wide locale, falling back to the configured default.
     */
    public function siteLocale(): string
    {
        return $this->locale ?: (string) config('app.locale');
    }

    /**
     * Get the site-wide timezone, falling back to the configured default.
     */
    public function siteTimezone(): string
    {
        return $this->timezone ?: (string) config('app.timezone');
    }

    /**
     * Get the public URL of the logo, falling back to the default kit logo.
     */
    public function logoUrl(): ?string
    {
        return $this->fileUrl($this->logo_path) ?? asset(self::DEFAULT_LOGO);
    }

    /**
     * Get the public URL of the dark-mode logo, falling back to the logo.
     */
    public function logoDarkUrl(): ?string
    {
        return $this->fileUrl($this->logo_dark_path) ?? $this->logoUrl();
    }

    /**
     * Get the public URL of the favicon, falling back to the default kit favicon.
     */
    public function faviconUrl(): ?string
    {
        return $this->fileUrl($this->favicon_path) ?? asset(self::DEFAULT_FAVICON);
    }

    /**
     * Get the public URL of the Apple touch icon, falling back to the default.
     */
    public function appleTouchIconUrl(): ?string
    {
        return $this->fileUrl($this->apple_touch_icon_path) ?? asset(self::DEFAULT_APPLE_TOUCH_ICON);
    }

    /**
     * Resolve a stored path into its public URL.
     */
    protected function fileUrl(?string $path): ?string
    {
        return $path === null ? null : Storage::disk('public')->url($path);
    }
}
