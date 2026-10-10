<?php

namespace App\Support\Navigation;

use Flux\FluxManager;

/**
 * Guards navigation icon names.
 *
 * `flux:icon` resolves `flux::icon.<name>` at render time and throws when the
 * icon does not exist, which would 500 the whole page. Any icon that comes
 * from root input or a package route tag is therefore validated here first and
 * degraded to a safe fallback when unknown.
 */
class NavigationIcon
{
    /**
     * Whether Flux can render `flux:icon name="<name>"`.
     */
    public static function exists(?string $name): bool
    {
        if ($name === null || trim($name) === '') {
            return false;
        }

        return app(FluxManager::class)->componentExists('icon.'.$name);
    }

    /**
     * A renderable icon name, falling back when the given one is unknown.
     */
    public static function safe(?string $name, string $fallback): string
    {
        return self::exists($name) ? (string) $name : $fallback;
    }

    /**
     * Like safe(), but keeps `null`/blank as `null` so the sidebar can omit the
     * icon entirely (for example a leading group without a heading).
     */
    public static function safeOrNull(?string $name, string $fallback): ?string
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        return self::exists($name) ? $name : $fallback;
    }
}
