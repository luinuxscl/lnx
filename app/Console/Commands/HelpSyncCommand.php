<?php

namespace App\Console\Commands;

use App\Actions\HelpSync;
use Illuminate\Console\Command;

class HelpSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'help:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Materialize the help entries declared by packages, preserving root overrides';

    /**
     * Execute the console command.
     */
    public function handle(HelpSync $sync): int
    {
        $summary = $sync->handle();

        $this->info(sprintf(
            'Help: %d created, %d updated, %d hidden.',
            $summary['created'],
            $summary['updated'],
            $summary['hidden'],
        ));

        return self::SUCCESS;
    }
}
