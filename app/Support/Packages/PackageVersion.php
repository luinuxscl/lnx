<?php

namespace App\Support\Packages;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Resolves the installed and the latest available version of a Composer
 * package, reading the installed version from Composer and the latest tag from
 * the GitHub API. It generalizes the previous single-package resolver so any
 * registered package (or, for `lnx-ui`, its own page) can report its versions.
 */
class PackageVersion
{
    public function __construct(
        private readonly string $name,
        private readonly ?string $repository = null,
    ) {}

    /**
     * The Composer name of the package.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * The GitHub repository (owner/name) used to look up tags.
     */
    public function repository(): string
    {
        return $this->repository ?? $this->name;
    }

    /**
     * The installed version, without the leading "v".
     */
    public function installedVersion(): string
    {
        if (! InstalledVersions::isInstalled($this->name())) {
            return __('Unknown');
        }

        $version = InstalledVersions::getPrettyVersion($this->name());

        return $version !== null ? ltrim($version, 'vV') : __('Unknown');
    }

    /**
     * The last update check result without hitting the network. Returns an
     * empty state when nothing has been checked yet.
     *
     * @return array{version: ?string, error: ?string, checked_at: ?string}
     */
    public function state(): array
    {
        return Cache::get($this->cacheKey(), $this->emptyState());
    }

    /**
     * Query the GitHub API for the newest tag and cache the result.
     *
     * @return array{version: ?string, error: ?string, checked_at: ?string}
     */
    public function refresh(): array
    {
        $state = $this->fetchLatestVersion();

        Cache::put($this->cacheKey(), $state, now()->addSeconds((int) config('packages.check_ttl', 3600)));

        return $state;
    }

    /**
     * Whether the latest known version is newer than the installed one.
     */
    public function isUpdateAvailable(?string $latestVersion): bool
    {
        if ($latestVersion === null) {
            return false;
        }

        return version_compare(ltrim($latestVersion, 'vV'), $this->installedVersion(), '>');
    }

    /**
     * The command a maintainer runs to update the package.
     */
    public function updateCommand(): string
    {
        return 'composer update '.$this->name();
    }

    private function cacheKey(): string
    {
        return 'packages:latest-version:'.$this->name();
    }

    /**
     * @return array{version: ?string, error: ?string, checked_at: ?string}
     */
    private function fetchLatestVersion(): array
    {
        if (blank(config('packages.token'))) {
            return $this->errorState('missing_token');
        }

        try {
            $response = Http::withToken((string) config('packages.token'))
                ->accept('application/vnd.github+json')
                ->timeout(10)
                ->get("https://api.github.com/repos/{$this->repository()}/tags", ['per_page' => 100]);
        } catch (Throwable) {
            return $this->errorState('request_failed');
        }

        if (! $response->successful()) {
            return $this->errorState('request_failed');
        }

        $tags = $response->json();

        if (! is_array($tags)) {
            return $this->errorState('no_tags');
        }

        $latest = collect($tags)
            ->pluck('name')
            ->filter(fn ($tag) => is_string($tag))
            ->map(fn ($tag) => ltrim($tag, 'vV'))
            ->filter(fn ($tag) => preg_match('/^\d+\.\d+\.\d+/', $tag) === 1)
            ->sort(fn ($a, $b) => version_compare($b, $a))
            ->first();

        if ($latest === null) {
            return $this->errorState('no_tags');
        }

        return [
            'version' => $latest,
            'error' => null,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{version: ?string, error: ?string, checked_at: ?string}
     */
    private function emptyState(): array
    {
        return ['version' => null, 'error' => null, 'checked_at' => null];
    }

    /**
     * @return array{version: ?string, error: ?string, checked_at: ?string}
     */
    private function errorState(string $error): array
    {
        return ['version' => null, 'error' => $error, 'checked_at' => now()->toIso8601String()];
    }
}
