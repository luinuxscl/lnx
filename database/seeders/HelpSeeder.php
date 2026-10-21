<?php

namespace Database\Seeders;

use App\Actions\HelpSync;
use Illuminate\Database\Seeder;

/**
 * Materializes the help center declared by the kit and its packages so a
 * fresh install renders from the database and root can then edit it.
 *
 * The synchronization is idempotent and respects root overrides, so running
 * it again after an upgrade never overwrites an editorial decision.
 */
class HelpSeeder extends Seeder
{
    public function run(): void
    {
        app(HelpSync::class)->handle();
    }
}
