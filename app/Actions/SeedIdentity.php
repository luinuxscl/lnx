<?php

namespace App\Actions;

use App\Support\Settings\IdentitySeederRegistry;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;

/**
 * Runs the registered identity seeders in priority order.
 *
 * Both the application seeder and the development reset command delegate here,
 * so the identity is populated the same way regardless of the entry point.
 */
class SeedIdentity
{
    public function __construct(private IdentitySeederRegistry $registry) {}

    /**
     * Run every registered identity seeder, highest priority first.
     *
     * @return int The number of seeders executed
     */
    public function handle(?Command $command = null): int
    {
        $executed = 0;

        foreach ($this->registry->all() as $class) {
            $seeder = app($class);

            if (! $seeder instanceof Seeder) {
                continue;
            }

            $seeder->setContainer(app());

            if ($command !== null) {
                $seeder->setCommand($command);
            }

            $seeder->__invoke();
            $executed++;
        }

        return $executed;
    }
}
