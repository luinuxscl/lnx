<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add provenance to the managed navigation and backfill existing rows.
     *
     * Items whose route is still tagged with the `menu` macro are treated as
     * catalog (package-owned); everything else (external links, untagged
     * routes and root-authored items) stays root-owned. A group becomes
     * catalog only when it holds at least one catalog item, so root-authored
     * groups keep full control. Menus render identically before and after.
     */
    public function up(): void
    {
        Schema::table('navigation_groups', function (Blueprint $table) {
            $table->string('source')->default('root')->after('enabled');
            $table->boolean('overridden')->default(false)->after('source');
        });

        Schema::table('navigation_items', function (Blueprint $table) {
            $table->string('source')->default('root')->after('enabled');
            $table->boolean('overridden')->default(false)->after('source');
        });

        $tagged = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if ($route->getName() !== null && is_array($route->defaults['menu'] ?? null)) {
                $tagged[] = $route->getName();
            }
        }

        if ($tagged !== []) {
            DB::table('navigation_items')
                ->where('type', 'route')
                ->whereIn('route_name', $tagged)
                ->update(['source' => 'catalog']);
        }

        $catalogGroupIds = DB::table('navigation_items')
            ->where('source', 'catalog')
            ->distinct()
            ->pluck('navigation_group_id')
            ->all();

        if ($catalogGroupIds !== []) {
            DB::table('navigation_groups')
                ->whereIn('id', $catalogGroupIds)
                ->update(['source' => 'catalog']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('navigation_items', function (Blueprint $table) {
            $table->dropColumn(['source', 'overridden']);
        });

        Schema::table('navigation_groups', function (Blueprint $table) {
            $table->dropColumn(['source', 'overridden']);
        });
    }
};
