<?php

namespace App\Providers;

use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{
    /**
     * Register the `menu` route macro used by lnx and by feature packages to
     * describe how a route should appear in the managed navigation.
     *
     * Usage:
     *     Route::get('reports', ...)->name('reports.index')->menu([
     *         'label' => 'Reports',        // translation key
     *         'icon' => 'chart-bar',
     *         'group' => 'Analytics',      // group label suggestion (key)
     *         'group_icon' => 'chart-bar',
     *         'audiences' => ['admin'],    // suggested audiences
     *         'roles' => ['admin'],        // allowed roles (omit for everyone)
     *         'auto' => true,              // autopublish in those audiences
     *         'order' => 2,                // default item order within the group
     *         'group_order' => 1,          // default order of the group
     *     ]);
     *
     * Autopublishing is opt-in per route (`auto => true`) or global via
     * `config('navigation.autopublish')`. An explicit `auto => false` always
     * wins over the global switch. `order` and `group_order` fall back to route
     * registration order when omitted.
     */
    public function boot(): void
    {
        Route::macro('menu', function (array $config): Route {
            /** @var Route $this */
            $this->defaults('menu', $config);

            return $this;
        });
    }
}
