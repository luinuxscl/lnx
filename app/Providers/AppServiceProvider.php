<?php

namespace App\Providers;

use App\Ai\AiApplicationRegistry;
use App\Enums\Role;
use App\Events\SupportPublicReplyAdded;
use App\Events\SupportTicketCreated;
use App\Events\SupportTicketStatusChanged;
use App\Listeners\SyncSupportTicketToMessaging;
use App\Mcp\McpToolRegistry;
use App\Models\InternalConversation;
use App\Models\User;
use App\Support\DevRootCredentials;
use App\Support\Notifications\NotificationTypeRegistry;
use App\Support\Packages\PackageRegistry;
use App\Support\Permissions\PermissionRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
        $this->app->singleton(AiApplicationRegistry::class);
        $this->app->singleton(McpToolRegistry::class);
        $this->app->singleton(NotificationTypeRegistry::class);
        $this->app->singleton(PackageRegistry::class);
        $this->app->singleton(PermissionRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->guardAgainstDevRootCredentials();
        $this->registerSupportMessagingSync();
    }

    protected function registerSupportMessagingSync(): void
    {
        Event::listen(SupportTicketCreated::class, [SyncSupportTicketToMessaging::class, 'handleTicketCreated']);
        Event::listen(SupportPublicReplyAdded::class, [SyncSupportTicketToMessaging::class, 'handlePublicReplyAdded']);
        Event::listen(SupportTicketStatusChanged::class, [SyncSupportTicketToMessaging::class, 'handleStatusChanged']);
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
     * The root role is a superuser: it bypasses every gate and policy check,
     * except for private internal conversations which require participation.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if (! $user->hasRole(Role::Root)) {
                return null;
            }

            // Root does not bypass privacy on non-support internal conversations.
            $target = $arguments[0] ?? null;
            if ($target instanceof InternalConversation && ! $target->isSupport()) {
                return null;
            }

            return true;
        });

        // Extension point for the reusable text assistant. Derived projects
        // define their own ability (a Gate or a policy ability) and pass it to
        // the component; root bypasses this gate via Gate::before.
        Gate::define('use-text-assistant', fn (User $user): bool => $user->hasRole(Role::Root));
        Gate::define('use-support-assistant', fn (User $user): bool => ! $user->isSuspended());
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
