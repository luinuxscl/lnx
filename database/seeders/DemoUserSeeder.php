<?php

namespace Database\Seeders;

use App\Support\DemoUsers;
use Illuminate\Database\Seeder;

/**
 * Seeds the fixed development accounts (Samantha and Leonora) plus a batch of
 * bulk sample users. Roles must already exist; run RolePermissionSeeder first.
 */
class DemoUserSeeder extends Seeder
{
    /**
     * Seed the demo accounts and the bulk sample users.
     */
    public function run(int $faker = DemoUsers::DEFAULT_FAKER_COUNT, ?int $seed = null): void
    {
        $users = app(DemoUsers::class);

        $accounts = $users->seedFixedAccounts();
        $bulk = $users->seedFakerUsers($faker, $seed);

        if ($this->command === null) {
            return;
        }

        $this->command->info(sprintf(
            'Seeded demo accounts: %s (admin), %s (user) and %d sample user(s) [%d verified, %d unverified].',
            $accounts['admin']->email,
            $accounts['user']->email,
            $bulk['verified'] + $bulk['unverified'],
            $bulk['verified'],
            $bulk['unverified'],
        ));
    }
}
