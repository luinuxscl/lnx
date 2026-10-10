<?php

namespace App\Support\Navigation;

use App\Enums\NavigationAudience;
use Illuminate\Support\Facades\Route;

/**
 * Reads the `menu` route tags into a normalized catalog.
 *
 * A single entry describes what a package wants for one route in one audience:
 * its label/icon, suggested group, allowed roles and autopublish intent. Both
 * the sidebar fallback (`MenuResolver`) and the reconciler (`NavigationSync`)
 * read from here so the tag contract lives in one place.
 */
class NavigationCatalog
{
    /**
     * Normalized entries for an audience, in route registration order.
     *
     * @return array<int, array{route_name: string, label_key: ?string, icon: ?string, group_key: ?string, group_icon: ?string, roles: array<int, string>|null, auto: bool, order: ?int, group_order: ?int, registration: int}>
     */
    public function entriesFor(NavigationAudience $audience): array
    {
        $entries = [];
        $registration = 0;

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $tag = $route->defaults['menu'] ?? null;
            $name = $route->getName();

            if (! is_array($tag) || $name === null) {
                continue;
            }

            $audiences = $tag['audiences'] ?? null;

            if (is_array($audiences) && ! in_array($audience->value, $audiences, true)) {
                continue;
            }

            $entries[] = [
                'route_name' => $name,
                'label_key' => isset($tag['label']) ? (string) $tag['label'] : null,
                'icon' => isset($tag['icon']) ? (string) $tag['icon'] : null,
                'group_key' => isset($tag['group']) ? (string) $tag['group'] : null,
                'group_icon' => isset($tag['group_icon']) ? (string) $tag['group_icon'] : null,
                'roles' => is_array($tag['roles'] ?? null) ? array_values(array_map('strval', $tag['roles'])) : null,
                'auto' => $this->autopublish($tag),
                'order' => isset($tag['order']) ? (int) $tag['order'] : null,
                'group_order' => isset($tag['group_order']) ? (int) $tag['group_order'] : null,
                'registration' => $registration++,
            ];
        }

        return $entries;
    }

    /**
     * Entries the reconciler is allowed to materialize on its own: `auto => true`
     * per route, or every tagged route when the global switch is on. An explicit
     * `auto => false` always opts out.
     *
     * @return array<int, array{route_name: string, label_key: ?string, icon: ?string, group_key: ?string, group_icon: ?string, roles: array<int, string>|null, auto: bool, order: ?int, group_order: ?int, registration: int}>
     */
    public function autopublishableFor(NavigationAudience $audience): array
    {
        return array_values(array_filter(
            $this->entriesFor($audience),
            fn (array $entry): bool => $entry['auto'],
        ));
    }

    /**
     * @return array{route_name: string, label_key: ?string, icon: ?string, group_key: ?string, group_icon: ?string, roles: array<int, string>|null, auto: bool, order: ?int, group_order: ?int, registration: int}|null
     */
    public function entryFor(NavigationAudience $audience, string $routeName): ?array
    {
        foreach ($this->entriesFor($audience) as $entry) {
            if ($entry['route_name'] === $routeName) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Whether a route currently carries a `menu` tag and belongs to the audience.
     */
    public function taggedFor(NavigationAudience $audience, ?string $routeName): bool
    {
        if ($routeName === null) {
            return false;
        }

        foreach ($this->entriesFor($audience) as $entry) {
            if ($entry['route_name'] === $routeName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $tag
     */
    private function autopublish(array $tag): bool
    {
        if (array_key_exists('auto', $tag)) {
            return (bool) $tag['auto'];
        }

        return (bool) config('navigation.autopublish', false);
    }
}
