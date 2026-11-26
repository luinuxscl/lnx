<?php

namespace App\Support\Dashboard;

use App\Models\User;
use InvalidArgumentException;

/**
 * In-memory registry of dashboard home surfaces declared by packages.
 */
class DashboardHomeRegistry
{
    /**
     * @var array<string, DashboardHome>
     */
    private array $homes = [];

    /**
     * @param  class-string<DashboardHome>  $home
     */
    public function register(string $source, string $home): void
    {
        $source = trim($source);

        if ($source === '') {
            throw new InvalidArgumentException('The dashboard home source must be a non-empty string.');
        }

        $instance = $this->resolve($home);

        if ($instance->source() !== $source) {
            throw new InvalidArgumentException(
                "The dashboard home declares source [{$instance->source()}] but was registered as [{$source}]."
            );
        }

        $this->homes[$source] = $instance;
    }

    /**
     * The home surface the actor should see, if any.
     */
    public function forActor(User $actor): ?DashboardHome
    {
        $visible = [];

        foreach ($this->homes as $home) {
            if ($home->visible($actor)) {
                $visible[] = $home;
            }
        }

        if ($visible === []) {
            return null;
        }

        usort($visible, static function (DashboardHome $left, DashboardHome $right): int {
            return [$right->priority(), $right->source()] <=> [$left->priority(), $left->source()];
        });

        return $visible[0];
    }

    /**
     * @param  class-string  $home
     */
    private function resolve(string $home): DashboardHome
    {
        if (! is_a($home, DashboardHome::class, true)) {
            throw new InvalidArgumentException(
                "The class [{$home}] must implement ".DashboardHome::class.'.'
            );
        }

        return app($home);
    }
}
