<?php

namespace App\Support\Packages;

use InvalidArgumentException;

/**
 * In-memory registry of the modules installed on the app.
 *
 * A package's service provider declares its own package metadata during boot;
 * the kit never lists a package just because it sits in `vendor/`. The generic
 * packages page (`/admin/packages`) reads this registry to show, for each
 * declared package, its installed version, the latest available one and the
 * update command. `lnx-ui` is not part of this registry: being the kit's own
 * presentational package with its design gallery, it keeps its dedicated page.
 */
class PackageRegistry
{
    /**
     * Declared packages, keyed by their Composer name.
     *
     * @var array<string, array{source: string, name: string, label: string, description: ?string, repository: string, url: ?string, update_command: string}>
     */
    private array $packages = [];

    /**
     * Declare the packages owned by a source (typically the module name).
     *
     * Each package must declare a Composer name (`vendor/package`) and a label;
     * the repository defaults to the name and the update command to
     * `composer update <name>`. A description, an external URL and an explicit
     * update command are optional.
     *
     * @param  array<string, mixed>  $package
     *
     * @throws InvalidArgumentException when a required field is missing or malformed
     */
    public function register(string $source, array $package): void
    {
        if ($source === '') {
            throw new InvalidArgumentException('The package source must be a non-empty string.');
        }

        $name = $package['name'] ?? null;
        if (! is_string($name) || ! $this->isPackageName($name)) {
            throw new InvalidArgumentException('The package must declare a Composer name like "vendor/package".');
        }

        $label = $package['label'] ?? null;
        if (! is_string($label) || $label === '') {
            throw new InvalidArgumentException("The package [{$name}] must declare a non-empty label.");
        }

        $repository = $package['repository'] ?? $name;
        if (! is_string($repository) || ! $this->isPackageName($repository)) {
            throw new InvalidArgumentException("The package [{$name}] must declare a repository like \"vendor/package\".");
        }

        $description = $package['description'] ?? null;
        if ($description !== null && ! is_string($description)) {
            throw new InvalidArgumentException("The package [{$name}] must declare a string description or null.");
        }

        $url = $package['url'] ?? null;
        if ($url !== null && (! is_string($url) || ! $this->isHttpUrl($url))) {
            throw new InvalidArgumentException("The package [{$name}] must declare an http(s) URL or null.");
        }

        $updateCommand = $package['update_command'] ?? 'composer update '.$name;
        if (! is_string($updateCommand) || $updateCommand === '') {
            throw new InvalidArgumentException("The package [{$name}] must declare a non-empty update command.");
        }

        $this->packages[$name] = [
            'source' => $source,
            'name' => $name,
            'label' => $label,
            'description' => $description,
            'repository' => $repository,
            'url' => $url,
            'update_command' => $updateCommand,
        ];
    }

    /**
     * Every declared package, in registration order.
     *
     * @return list<array{source: string, name: string, label: string, description: ?string, repository: string, url: ?string, update_command: string}>
     */
    public function all(): array
    {
        return array_values($this->packages);
    }

    /**
     * Whether any package has been declared.
     */
    public function isEmpty(): bool
    {
        return $this->packages === [];
    }

    private function isPackageName(string $value): bool
    {
        return preg_match('#^[a-z0-9_.-]+/[a-z0-9_.-]+$#i', $value) === 1;
    }

    private function isHttpUrl(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true);
    }
}
