<?php

namespace App\Support\Navigation;

use App\Enums\NavigationAudience;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache keys for the resolved navigation structure. Any write to a
 * group or item bumps the version, so every audience key is invalidated at
 * once without having to enumerate keys.
 */
class NavigationCache
{
    private const VERSION_KEY = 'navigation:version';

    /**
     * Bump when the cached structure shape changes, so entries written before
     * the change (e.g. before the `region` key existed) are never re-read.
     */
    private const STRUCTURE_VERSION = 2;

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    public static function key(NavigationAudience $audience): string
    {
        return 'navigation:structure:s'.self::STRUCTURE_VERSION.':v'.self::version().':'.$audience->value;
    }

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
