<?php

namespace App\Console\Commands;

use App\Actions\SeedIdentity;
use App\Support\DemoUsers;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\HelpSeeder;
use Database\Seeders\NavigationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;

/**
 * Resets the development environment: clears caches, migrates a fresh
 * database and reseeds roles, the root user and the demo accounts.
 *
 * It is a development tool and is disabled in production.
 */
class LnxResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lnx:reset
                            {--faker=100 : Number of bulk sample users to create (0 skips them)}
                            {--no-fresh : Seed onto the existing database without migrating}
                            {--no-cache : Skip clearing the application caches}
                            {--seed= : Faker seed for reproducible sample data}
                            {--force : Skip the confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the development environment: fresh database, root user and demo accounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('lnx:reset is a development tool and is disabled in production.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This will erase all data. Continue?', false)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        if (! $this->option('no-cache')) {
            $this->callSilent('optimize:clear');
            $this->info('Caches cleared.');
        }

        if (! $this->option('no-fresh')) {
            $this->info('Migrating a fresh database...');
            $this->callSilent('migrate:fresh', ['--force' => true]);
        }

        $this->seed(RolePermissionSeeder::class);
        $this->seed(NavigationSeeder::class);
        $this->seed(HelpSeeder::class);

        app(SeedIdentity::class)->handle($this);

        $this->seed(DemoUserSeeder::class, [
            'faker' => max(0, (int) $this->option('faker')),
            'seed' => $this->fakerSeed(),
        ]);

        $this->newLine();
        $this->info('Development environment reset complete.');

        $this->table(['Account', 'Email', 'Password'], [
            ['Root', (string) config('authorization.root_user.email'), 'as configured'],
            ['Admin', DemoUsers::ADMIN_EMAIL, DemoUsers::PASSWORD],
            ['User', DemoUsers::USER_EMAIL, DemoUsers::PASSWORD],
            ['Sample', $this->option('faker').' users', DemoUsers::PASSWORD],
        ]);

        return self::SUCCESS;
    }

    /**
     * Run a seeder with the current command as its output channel.
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function seed(string $class, array $parameters = []): Seeder
    {
        $seeder = $this->getLaravel()->make($class);
        $seeder->setContainer($this->getLaravel())->setCommand($this);
        $seeder->__invoke($parameters);

        return $seeder;
    }

    /**
     * The optional Faker seed as an integer, or null when not provided.
     */
    protected function fakerSeed(): ?int
    {
        $seed = $this->option('seed');

        return ($seed === null || $seed === '') ? null : (int) $seed;
    }
}
