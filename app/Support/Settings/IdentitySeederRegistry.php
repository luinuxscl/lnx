<?php

namespace App\Support\Settings;

use App\Actions\ApplyIdentityDefaults;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

/**
 * In-memory registry of the identity seeders declared by installed packages.
 *
 * A package's service provider declares its own identity defaults during boot;
 * the kit never seeds a value just because a package sits in `vendor/`. The
 * kit registers its own base identity seeder with the lowest priority, so the
 * packages' seeders run first and win at equal fields. Each seeder applies its
 * defaults through {@see ApplyIdentityDefaults}, which only fills
 * values that are still undefined and never overwrites what root saved.
 */
class IdentitySeederRegistry
{
    /**
     * Priority used by the kit's own base identity seeder.
     *
     * Any package default (including the implicit `0`) wins over it, so the
     * kit baseline only fills whatever no package declared.
     */
    public const KIT_PRIORITY = PHP_INT_MIN;

    /**
     * Declared seeders, keyed by the package source that declared them.
     *
     * @var array<string, array{seeder: class-string<Seeder>, priority: int, order: int}>
     */
    private array $seeders = [];

    /**
     * Monotonic counter used to keep a stable order between equal priorities.
     */
    private int $order = 0;

    /**
     * Declare the identity seeder owned by a package.
     *
     * The seeder class must extend {@see Seeder}; the kit runs it in priority
     * order (highest first) when the database is seeded.
     *
     * @param  class-string<Seeder>|string  $seeder
     *
     * @throws InvalidArgumentException when the source is empty or the seeder is not a Seeder
     */
    public function register(string $source, string $seeder, int $priority = 0): void
    {
        if ($source === '') {
            throw new InvalidArgumentException('The identity seeder source must be a non-empty string.');
        }

        if (! is_subclass_of($seeder, Seeder::class)) {
            throw new InvalidArgumentException("Identity seeder [{$seeder}] must extend ".Seeder::class.'.');
        }

        $this->seeders[$source] = [
            'seeder' => $seeder,
            'priority' => $priority,
            'order' => $this->seeders[$source]['order'] ?? $this->order++,
        ];
    }

    /**
     * Every declared seeder class, ordered by priority (highest first).
     *
     * Seeders that share a priority keep their registration order, so the
     * result is deterministic.
     *
     * @return list<class-string<Seeder>>
     */
    public function all(): array
    {
        $seeders = $this->seeders;

        uasort($seeders, fn (array $a, array $b): int => $b['priority'] <=> $a['priority'] ?: $a['order'] <=> $b['order']);

        return array_values(array_map(
            fn (array $entry): string => $entry['seeder'],
            $seeders,
        ));
    }

    /**
     * Whether any identity seeder has been declared.
     */
    public function isEmpty(): bool
    {
        return $this->seeders === [];
    }
}
