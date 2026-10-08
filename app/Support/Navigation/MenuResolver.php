<?php

namespace App\Support\Navigation;

use App\Enums\NavigationAudience;
use App\Enums\NavigationItemType;
use App\Models\NavigationMenu;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Resolves the sidebar tree for a given user.
 *
 * The database is the source of truth once seeded. When no menu row exists for
 * the audience (for example a brand new install before seeding), the tree is
 * derived from the routes that packages tagged with the `menu` macro, so the
 * sidebar never renders empty.
 */
class MenuResolver
{
    public function __construct(private readonly NavigationCatalog $catalog) {}

    /**
     * @return array<int, array{label: ?string, icon: ?string, items: array<int, array{label: string, icon: ?string, href: ?string, current: bool, external: bool}>}>
     */
    public function resolve(User $user): array
    {
        $audience = NavigationAudience::forUser($user);
        $groups = [];

        foreach ($this->structure($audience) as $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                $resolved = $this->resolveItem($item, $user);

                if ($resolved !== null) {
                    $items[] = $resolved;
                }
            }

            if ($items !== []) {
                $groups[] = [
                    'label' => $this->resolveLabel($group),
                    'icon' => $group['icon'],
                    'items' => $items,
                ];
            }
        }

        return $groups;
    }

    /**
     * @return array<int, array{label_key: ?string, label_es: ?string, label_en: ?string, icon: ?string, items: array<int, array<string, mixed>>}>
     */
    private function structure(NavigationAudience $audience): array
    {
        $cacheKey = NavigationCache::key($audience);

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $menu = NavigationMenu::query()->where('audience', $audience)->first();

        $structure = $menu !== null
            ? $this->fromDatabase($menu)
            : $this->fromRouteTags($audience);

        Cache::put($cacheKey, $structure, now()->addSeconds((int) config('navigation.cache_ttl', 3600)));

        return $structure;
    }

    /**
     * @return array<int, array{label_key: ?string, label_es: ?string, label_en: ?string, icon: ?string, items: array<int, array<string, mixed>>}>
     */
    private function fromDatabase(NavigationMenu $menu): array
    {
        $menu->load([
            'groups' => fn ($query) => $query->where('enabled', true),
            'groups.items' => fn ($query) => $query->where('enabled', true),
        ]);

        $groups = [];

        foreach ($menu->groups as $group) {
            $items = [];

            foreach ($group->items as $item) {
                $items[] = [
                    'type' => $item->type->value,
                    'route_name' => $item->route_name,
                    'url' => $item->url,
                    'label_key' => $item->label_key,
                    'label_es' => $item->label_es,
                    'label_en' => $item->label_en,
                    'icon' => $item->icon,
                ];
            }

            $groups[] = [
                'label_key' => $group->label_key,
                'label_es' => $group->label_es,
                'label_en' => $group->label_en,
                'icon' => $group->icon,
                'items' => $items,
            ];
        }

        return $groups;
    }

    /**
     * @return array<int, array{label_key: ?string, label_es: null, label_en: null, icon: ?string, items: array<int, array<string, mixed>>}>
     */
    private function fromRouteTags(NavigationAudience $audience): array
    {
        $groups = [];

        foreach ($this->catalog->entriesFor($audience) as $entry) {
            $index = $entry['group_key'] ?? '';

            if (! isset($groups[$index])) {
                $groups[$index] = [
                    'label_key' => $entry['group_key'],
                    'label_es' => null,
                    'label_en' => null,
                    'icon' => $entry['group_icon'],
                    'items' => [],
                ];
            }

            $groups[$index]['items'][] = [
                'type' => NavigationItemType::Route->value,
                'route_name' => $entry['route_name'],
                'url' => null,
                'label_key' => $entry['label_key'],
                'label_es' => null,
                'label_en' => null,
                'icon' => $entry['icon'],
            ];
        }

        return array_values($groups);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{label: string, icon: ?string, href: ?string, current: bool, external: bool}|null
     */
    private function resolveItem(array $item, User $user): ?array
    {
        if (($item['type'] ?? null) === NavigationItemType::Link->value) {
            $label = $this->resolveLabel($item);

            if ($label === null) {
                return null;
            }

            $url = $item['url'] ?? null;

            return [
                'label' => $label,
                'icon' => $item['icon'] ?? null,
                'href' => $url,
                'current' => false,
                'external' => $url !== null && preg_match('#^https?://#i', $url) === 1,
            ];
        }

        $routeName = $item['route_name'] ?? null;

        if ($routeName === null || ! Route::has($routeName)) {
            return null;
        }

        $route = Route::getRoutes()->getByName($routeName);
        $tag = is_array($route?->defaults['menu'] ?? null) ? $route->defaults['menu'] : [];

        $roles = $tag['roles'] ?? null;

        if (is_array($roles) && $roles !== [] && ! $user->hasAnyRole($roles)) {
            return null;
        }

        $label = $this->resolveRouteLabel($tag, $item);

        if ($label === null) {
            return null;
        }

        $match = $tag['match'] ?? $routeName;

        return [
            'label' => $label,
            'icon' => $tag['icon'] ?? ($item['icon'] ?? null),
            'href' => route($routeName),
            'current' => request()->routeIs($match),
            'external' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $tag
     * @param  array<string, mixed>  $item
     */
    private function resolveRouteLabel(array $tag, array $item): ?string
    {
        if (isset($tag['label'])) {
            return __($tag['label']);
        }

        return $this->resolveLabel($item);
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function resolveLabel(array $source): ?string
    {
        if (($source['label_key'] ?? null) !== null) {
            return __($source['label_key']);
        }

        $es = $source['label_es'] ?? null;
        $en = $source['label_en'] ?? null;

        if (app()->getLocale() === 'en') {
            return $en ?? $es;
        }

        return $es ?? $en;
    }
}
