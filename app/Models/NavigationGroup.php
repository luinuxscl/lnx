<?php

namespace App\Models;

use App\Enums\NavigationSource;
use App\Support\Navigation\NavigationCache;
use App\Support\Navigation\NavigationIcon;
use Database\Factories\NavigationGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $navigation_menu_id
 * @property string|null $label_key
 * @property string|null $label_es
 * @property string|null $label_en
 * @property string|null $icon
 * @property int $position
 * @property bool $enabled
 * @property NavigationSource $source
 * @property bool $overridden
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['navigation_menu_id', 'label_key', 'label_es', 'label_en', 'icon', 'position', 'enabled', 'source', 'overridden'])]
class NavigationGroup extends Model
{
    /** @use HasFactory<NavigationGroupFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'position' => 'integer',
            'source' => NavigationSource::class,
            'overridden' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => NavigationCache::flush());
        static::deleted(fn () => NavigationCache::flush());
    }

    /**
     * @return BelongsTo<NavigationMenu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'navigation_menu_id');
    }

    /**
     * @return HasMany<NavigationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Resolve the localized heading. A null label means the group renders
     * without a heading (used for leading items such as the dashboard).
     */
    public function label(): ?string
    {
        if ($this->label_key !== null) {
            return __($this->label_key);
        }

        return app()->getLocale() === 'en'
            ? ($this->label_en ?? $this->label_es)
            : ($this->label_es ?? $this->label_en);
    }

    /**
     * Icon shown in the admin list, degrading an unknown name to a safe
     * fallback so Flux never throws while rendering.
     */
    public function menuIcon(): string
    {
        return NavigationIcon::safe($this->icon, 'folder');
    }

    /**
     * Provenance status used by the admin UI (see NavigationItem::status()).
     */
    public function status(): string
    {
        if ($this->source === NavigationSource::Root) {
            return 'root';
        }

        return $this->overridden ? 'overridden' : 'auto';
    }
}
