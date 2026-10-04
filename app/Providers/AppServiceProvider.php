<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use App\Support\DevRootCredentials;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->guardAgainstDevRootCredentials();
    }

    /**
     * Log a critical warning when production still uses the development root password.
     *
     * Runs on console commands (deploys, seeders) only, to avoid a per-request
     * database query. The UI banner covers runtime visibility.
     */
    protected function guardAgainstDevRootCredentials(): void
    {
        if (! app()->isProduction() || ! app()->runningInConsole()) {
            return;
        }

        try {
            if (DevRootCredentials::isActive()) {
                Log::critical(
                    'The root user is still using the default development password in production. '
                    .'Change it immediately.'
                );
            }
        } catch (\Throwable) {
            // The database may not be migrated yet; the seeder guard remains the hard stop.
        }
    }

    /**
     * Configure role-based authorization.
     *
     * The root role is a superuser: it bypasses every gate and policy check.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(fn (User $user, string $ability): ?bool => $user->hasRole(Role::Root) ? true : null);

        // Extension point for the reusable text assistant. Derived projects
        // define their own ability (a Gate or a policy ability) and pass it to
        // the component; root bypasses this gate via Gate::before.
        Gate::define('use-text-assistant', fn (User $user): bool => $user->hasRole(Role::Root));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);
        Number::useLocale('es');

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
