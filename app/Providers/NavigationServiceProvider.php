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
     *     ]);
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
