<?php

namespace App\Support\Help;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * In-memory registry of the help articles and FAQs declared by installed packages.
 *
 * A package's service provider registers its own content during boot; the kit
 * never renders help just because a package sits in `vendor/`. Entries are
 * filtered by the roles of the authenticated identity, so a restricted article
 * never leaks through the list, the search or a deep link.
 */
class HelpRegistry
{
    public const TYPES = ['article', 'faq'];

    /**
     * Declared entries, keyed by their slug.
     *
     * @var array<string, array{source: string, slug: string, type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null}>
     */
    private array $entries = [];

    /**
     * Declare the help entries owned by a package.
     *
     * Each entry is keyed by a slug prefixed with the package source
     * (e.g. `luz.primeros-pasos`) and declares its translations as keys, not
     * as resolved text, so the content follows the current locale.
     *
     * @param  array<string, mixed>  $entries
     *
     * @throws InvalidArgumentException when a declaration is incomplete or malformed
     */
    public function register(string $source, array $entries): void
    {
        if ($source === '') {
            throw new InvalidArgumentException('The package source must be a non-empty string.');
        }

        foreach ($entries as $slug => $entry) {
            if (! is_string($slug) || $slug === '') {
                throw new InvalidArgumentException('Help slugs must be non-empty strings.');
            }

            if (! str_starts_with($slug, $source.'.')) {
                throw new InvalidArgumentException(
                    "Help entry [{$slug}] must be prefixed with the package source [{$source}.]."
                );
            }

            if (isset($this->entries[$slug])) {
                throw new InvalidArgumentException("Help entry [{$slug}] is already registered.");
            }

            if (! is_array($entry)) {
                throw new InvalidArgumentException("Help entry [{$slug}] must be an array.");
            }

            $type = $entry['type'] ?? null;
            if (! is_string($type) || ! in_array($type, self::TYPES, true)) {
                throw new InvalidArgumentException(
                    "Help entry [{$slug}] must declare a type of: ".implode(', ', self::TYPES).'.'
                );
            }

            $this->entries[$slug] = [
                'source' => $source,
                'slug' => $slug,
                'type' => $type,
                'group' => $this->requiredString($slug, $entry, 'group'),
                'title' => $this->requiredString($slug, $entry, 'title'),
                'summary' => $this->requiredString($slug, $entry, 'summary'),
                'body' => $this->requiredString($slug, $entry, 'body'),
                'roles' => $this->roles($slug, $entry['roles'] ?? []),
                'order' => $this->order($slug, $entry['order'] ?? 0),
                'route' => $this->route($slug, $entry['route'] ?? null),
            ];
        }
    }

    /**
     * Every entry the identity may read, ordered by their declared order.
     *
     * @return list<array{source: string, slug: string, type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null}>
     */
    public function all(User $user): array
    {
        $entries = array_values(array_filter(
            $this->entries,
            fn (array $entry): bool => $this->isVisible($entry, $user),
        ));

        usort($entries, fn (array $left, array $right): int => [$left['order'], $left['slug']] <=> [$right['order'], $right['slug']]);

        return $entries;
    }

    /**
     * A readable entry by slug, or null when it does not exist or is restricted.
     *
     * @return array{source: string, slug: string, type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null}|null
     */
    public function find(string $slug, User $user): ?array
    {
        $entry = $this->entries[$slug] ?? null;

        return $entry !== null && $this->isVisible($entry, $user) ? $entry : null;
    }

    /**
     * Readable entries whose title, summary or body contain the term.
     *
     * The comparison is case-insensitive and runs against the content
     * resolved to the current locale. A blank term returns every readable
     * entry.
     *
     * @return list<array{source: string, slug: string, type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null}>
     */
    public function search(string $term, User $user): array
    {
        $term = Str::lower(trim($term));

        if ($term === '') {
            return $this->all($user);
        }

        return array_values(array_filter(
            $this->all($user),
            function (array $entry) use ($term): bool {
                $haystack = Str::lower(implode("\n", [
                    (string) __($entry['title']),
                    (string) __($entry['summary']),
                    (string) __($entry['body']),
                ]));

                return Str::contains($haystack, $term);
            },
        ));
    }

    /**
     * Render an entry body as safe Markdown in the current locale.
     *
     * Raw HTML is stripped and unsafe links are never rendered, so package
     * content cannot inject markup or `javascript:` URLs.
     *
     * @param  array{body: string}  $entry
     */
    public function bodyHtml(array $entry): string
    {
        return Str::markdown((string) __($entry['body']), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * The URL of the entry's optional feature route, when it exists.
     *
     * @param  array{route: string|null}  $entry
     */
    public function routeUrl(array $entry): ?string
    {
        return $entry['route'] !== null && Route::has($entry['route'])
            ? route($entry['route'])
            : null;
    }

    /**
     * A registered entry is visible when it has no role restriction, when the
     * identity holds one of its roles, or when the identity is root.
     *
     * @param  array{roles: list<string>}  $entry
     */
    private function isVisible(array $entry, User $user): bool
    {
        if ($user->hasRole(Role::Root)) {
            return true;
        }

        if ($entry['roles'] === []) {
            return true;
        }

        foreach ($entry['roles'] as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function requiredString(string $slug, array $entry, string $field): string
    {
        $value = $entry[$field] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException("Help entry [{$slug}] must declare a non-empty {$field}.");
        }

        return $value;
    }

    /**
     * Normalize the declared roles to unique enum values.
     *
     * An empty list means every authenticated identity may read the entry;
     * root always sees every declared entry.
     *
     * @return list<string>
     */
    private function roles(string $slug, mixed $roles): array
    {
        if (! is_array($roles)) {
            throw new InvalidArgumentException("Help entry [{$slug}] must declare roles as an array.");
        }

        $values = [];

        foreach ($roles as $role) {
            $value = $role instanceof Role ? $role->value : $role;

            if (! is_string($value) || Role::tryFrom($value) === null) {
                throw new InvalidArgumentException("Help entry [{$slug}] declares an unknown role.");
            }

            if (! in_array($value, $values, true)) {
                $values[] = $value;
            }
        }

        return $values;
    }

    private function order(string $slug, mixed $order): int
    {
        if (! is_int($order)) {
            throw new InvalidArgumentException("Help entry [{$slug}] must declare an integer order.");
        }

        return $order;
    }

    private function route(string $slug, mixed $route): ?string
    {
        if ($route === null) {
            return null;
        }

        if (! is_string($route) || $route === '') {
            throw new InvalidArgumentException("Help entry [{$slug}] must declare a non-empty route or null.");
        }

        return $route;
    }
}
