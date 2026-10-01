<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
 * @property string|null $analytics_id
 * @property bool $maintenance_mode
 * @property string|null $maintenance_message
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
    'analytics_id',
    'maintenance_mode',
    'maintenance_message',
    'registration_mode',
])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected $attributes = ['registration_mode' => 'open'];

    public static function registrationIsOpen(): bool
    {
        return self::current()->registration_mode !== 'invitation_only';
    }

    /**
     * The cache key used to store the singleton settings row.
     */
    public const CACHE_KEY = 'settings';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'maintenance_mode' => 'boolean',
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
        return $this->site_title ?: (string) config('app.name', 'Laravel');
    }

    /**
     * Get the meta description, falling back to the tagline.
     */
    public function description(): ?string
    {
        return $this->meta_description ?: $this->site_tagline;
    }

    /**
     * Get the public URL of the logo.
     */
    public function logoUrl(): ?string
    {
        return $this->fileUrl($this->logo_path);
    }

    /**
     * Get the public URL of the dark-mode logo, falling back to the logo.
     */
    public function logoDarkUrl(): ?string
    {
        return $this->fileUrl($this->logo_dark_path) ?? $this->logoUrl();
    }

    /**
     * Get the public URL of the favicon.
     */
    public function faviconUrl(): ?string
    {
        return $this->fileUrl($this->favicon_path);
    }

    /**
     * Get the public URL of the Apple touch icon.
     */
    public function appleTouchIconUrl(): ?string
    {
        return $this->fileUrl($this->apple_touch_icon_path);
    }

    /**
     * Resolve a stored path into its public URL.
     */
    protected function fileUrl(?string $path): ?string
    {
        return $path === null ? null : Storage::disk('public')->url($path);
    }
}
