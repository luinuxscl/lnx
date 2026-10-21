<?php

use App\Support\Navigation\NavigationCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the help editor entry to the root menu of every existing install.
     *
     * Fresh installs get it from `NavigationSeeder`; this migration backfills
     * installations whose menus are already materialized. It is idempotent and
     * preserves root overrides.
     */
    public function up(): void
    {
        $now = now();

        foreach (DB::table('navigation_menus')->where('audience', 'root')->pluck('id') as $menuId) {
            $alreadyPresent = DB::table('navigation_items')
                ->join('navigation_groups', 'navigation_groups.id', '=', 'navigation_items.navigation_group_id')
                ->where('navigation_groups.navigation_menu_id', $menuId)
                ->where('navigation_items.route_name', 'admin.help.index')
                ->exists();

            if ($alreadyPresent) {
                continue;
            }

            $group = DB::table('navigation_groups')
                ->where('navigation_menu_id', $menuId)
                ->where('region', 'bottom')
                ->where('label_key', 'Developer Mode')
                ->first();

            if ($group === null) {
                continue;
            }

            $position = (int) DB::table('navigation_items')
                ->where('navigation_group_id', $group->id)
                ->max('position');

            DB::table('navigation_items')->insert([
                'navigation_group_id' => $group->id,
                'type' => 'route',
                'route_name' => 'admin.help.index',
                'icon' => 'book-open',
                'position' => $position + 1,
                'enabled' => true,
                'source' => 'root',
                'overridden' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        NavigationCache::flush();
    }

    /**
     * Remove only the backfilled help editor items.
     */
    public function down(): void
    {
        DB::table('navigation_items')->where('route_name', 'admin.help.index')->delete();

        NavigationCache::flush();
    }
};
