<?php

namespace App\Support;

class AppVersion
{
    /**
     * Placeholder for installations where no real version can be resolved
     * (no APP_VERSION set, no Git metadata and no cached version).
     */
    public const FALLBACK = '0.0.0-dev';

    /**
     * Resolve the application version.
     *
     * Precedence: an explicit APP_VERSION in the environment (surfaced
     * through config) wins, then the cache file written by
     * `php artisan app:version`, then the fallback placeholder. The cache
     * file is re-read per call (cheap under opcache) so deploys that
     * regenerate it are picked up without restarting workers.
     */
    public static function resolve(?string $cachePath = null): string
    {
        $configured = (string) config('app.version', self::FALLBACK);

        if ($configured !== self::FALLBACK) {
            return $configured;
        }

        return static::readCache($cachePath) ?? self::FALLBACK;
    }

    /**
     * Read the version cache file written by the app:version command.
     */
    public static function readCache(?string $cachePath = null): ?string
    {
        $cachePath ??= static::cachePath();

        if (! is_file($cachePath)) {
            return null;
        }

        $version = include $cachePath;

        return is_string($version) && filled($version) ? $version : null;
    }

    /**
     * Turn raw `git describe --tags --long --always --dirty` output into a
     * display version: an exact tag renders clean (v1.2.3-0-gabc → v1.2.3),
     * anything else passes through (v1.2.3-2-gabc, a bare commit hash,
     * with an optional -dirty suffix).
     */
    public static function normalize(string $gitDescribe): string
    {
        $describe = trim($gitDescribe);

        if (preg_match('~^(?P<tag>.+)-0-g(?P<hash>[0-9a-f]{7,})(?P<dirty>-dirty)?$~', $describe, $matches) === 1) {
            return $matches['tag'].$matches['dirty'];
        }

        return $describe;
    }

    /**
     * Path of the version cache file, following the config:cache convention.
     */
    public static function cachePath(): string
    {
        return base_path('bootstrap/cache/app-version.php');
    }
}
