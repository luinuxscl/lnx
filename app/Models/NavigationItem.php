<?php

namespace App\Models;

use App\Enums\NavigationItemType;
use App\Enums\NavigationSource;
use App\Support\Navigation\NavigationCache;
use App\Support\Navigation\NavigationIcon;
use Database\Factories\NavigationItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

/**
 * @property int $id
 * @property int $navigation_group_id
 * @property NavigationItemType $type
 * @property string|null $route_name
 * @property string|null $url
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
#[Fillable(['navigation_group_id', 'type', 'route_name', 'url', 'label_key', 'label_es', 'label_en', 'icon', 'position', 'enabled', 'source', 'overridden'])]
class NavigationItem extends Model
{
    /** @use HasFactory<NavigationItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => NavigationItemType::class,
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
     * @return BelongsTo<NavigationGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(NavigationGroup::class, 'navigation_group_id');
    }

    /**
     * Resolve the localized label for a stored (root-authored) label.
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
     * The label shown in the admin list: the route's tag label when available,
     * otherwise the stored label.
     */
    public function menuLabel(): string
    {
        $tag = $this->routeTag();

        if (isset($tag['label'])) {
            return (string) __($tag['label']);
        }

        if ($this->type === NavigationItemType::Route && $this->route_name !== null && ! Route::has($this->route_name)) {
            return $this->route_name;
        }

        return (string) ($this->label() ?? $this->route_name ?? $this->url ?? '');
    }

    /**
     * Icon shown in the admin list. A route tag icon wins over the stored one;
     * an unknown name degrades to a type-appropriate fallback so Flux never
     * throws while rendering.
     */
    public function menuIcon(): ?string
    {
        $tag = $this->routeTag();
        $icon = $tag['icon'] ?? $this->icon;
        $icon = is_string($icon) ? $icon : null;

        return NavigationIcon::safeOrNull(
            $icon,
            $this->type === NavigationItemType::Route ? 'link' : 'arrow-top-right-on-square',
        );
    }

    public function destination(): ?string
    {
        return $this->type === NavigationItemType::Route ? $this->route_name : $this->url;
    }

    /**
     * Provenance status used by the admin UI: an item auto-published from a
     * package that root has not touched (`auto`), one root has edited
     * (`overridden`) or one root authored from scratch (`root`).
     */
    public function status(): string
    {
        if ($this->source === NavigationSource::Root) {
            return 'root';
        }

        return $this->overridden ? 'overridden' : 'auto';
    }

    /**
     * @return array<string, mixed>
     */
    public function routeTag(): array
    {
        if ($this->type !== NavigationItemType::Route || $this->route_name === null) {
            return [];
        }

        $route = Route::getRoutes()->getByName($this->route_name);
        $tag = $route?->defaults['menu'] ?? null;

        return is_array($tag) ? $tag : [];
    }
}
