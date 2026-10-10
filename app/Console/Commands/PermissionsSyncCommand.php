<?php

namespace App\Console\Commands;

use App\Actions\SyncPermissions;
use Illuminate\Console\Command;

class PermissionsSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile the permissions declared by packages with the database';

    /**
     * Execute the console command.
     */
    public function handle(SyncPermissions $sync): int
    {
        $summary = $sync->handle();

        $this->info(sprintf(
            'Permissions: %d created, %d granted to admin.',
            $summary['created'],
            $summary['granted'],
        ));

        return self::SUCCESS;
    }
}
