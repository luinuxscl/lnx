<?php

namespace App\Ai;

use InvalidArgumentException;

/**
 * In-memory registry of the AI applications declared by installed packages.
 *
 * A package's service provider declares its own applications, each with its
 * own quota, during boot; the kit never exposes an AI application just because
 * a package sits in `vendor/`. The registry is read by {@see AiConfig} when it
 * resolves an application's quota or lists the configured applications, so a
 * declared application is enforced and recorded without editing
 * `config/lnx_ai.php`. A deployment can still override a package quota from
 * configuration; the registry provides the default.
 */
class AiApplicationRegistry
{
    /**
     * Declared applications, keyed by application name.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $applications = [];

    /**
     * Declare the AI applications owned by a package.
     *
     * Names must be prefixed with the package source (e.g. `luz.extraction`)
     * so ownership stays explicit and a typo or collision is detectable. Each
     * application maps to its quota (window, requests, tokens); a deployment
     * can override it from configuration.
     *
     * @param  array<array-key, mixed>  $applications
     *
     * @throws InvalidArgumentException when a name is empty or not namespaced
     */
    public function register(string $source, array $applications): void
    {
        if ($source === '') {
            throw new InvalidArgumentException('The package source must be a non-empty string.');
        }

        foreach ($applications as $name => $quota) {
            if (! is_string($name) || $name === '') {
                throw new InvalidArgumentException('Application names must be non-empty strings.');
            }

            if (! str_starts_with($name, $source.'.')) {
                throw new InvalidArgumentException(
                    "Application [{$name}] must be prefixed with the package source [{$source}.]."
                );
            }

            $this->applications[$name] = is_array($quota) ? $quota : [];
        }
    }

    /**
     * Whether a package declared the given application.
     */
    public function has(string $application): bool
    {
        return array_key_exists($application, $this->applications);
    }

    /**
     * The declared quota for an application, or null when it is not declared.
     *
     * @return array<string, mixed>|null
     */
    public function quota(string $application): ?array
    {
        return $this->applications[$application] ?? null;
    }

    /**
     * Every declared application, keyed by name.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->applications;
    }
}
