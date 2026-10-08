<?php

namespace App\Console\Commands;

use App\Actions\NavigationSync;
use App\Enums\NavigationAudience;
use Illuminate\Console\Command;

class NavigationSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'navigation:sync {--audience= : Reconcile only one audience (root, admin or user)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile the managed navigation with the routes tagged by packages';

    /**
     * Execute the console command.
     */
    public function handle(NavigationSync $sync): int
    {
        $audience = null;

        if ($this->option('audience') !== null) {
            $audience = NavigationAudience::tryFrom((string) $this->option('audience'));

            if ($audience === null) {
                $this->error('Unknown audience. Use root, admin or user.');

                return self::FAILURE;
            }
        }

        $summary = $sync->handle($audience);

        foreach ($summary as $name => $counts) {
            $this->info(sprintf(
                '%s: %d created, %d updated, %d hidden.',
                ucfirst($name),
                $counts['created'],
                $counts['updated'],
                $counts['hidden'],
            ));
        }

        return self::SUCCESS;
    }
}
