<?php

use App\Support\Navigation\NavigationCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the help center entry to the bottom region of every existing menu.
     *
     * Fresh installs get it from `NavigationSeeder`; this migration backfills
     * installations that already have their menus materialized. It is
     * idempotent and preserves root overrides.
     */
    public function up(): void
    {
        $now = now();

        foreach (DB::table('navigation_menus')->pluck('id') as $menuId) {
            $alreadyPresent = DB::table('navigation_items')
                ->join('navigation_groups', 'navigation_groups.id', '=', 'navigation_items.navigation_group_id')
                ->where('navigation_groups.navigation_menu_id', $menuId)
                ->where('navigation_items.route_name', 'help.index')
                ->exists();

            if ($alreadyPresent) {
                continue;
            }

            $group = DB::table('navigation_groups')
                ->where('navigation_menu_id', $menuId)
                ->where('region', 'bottom')
                ->orderBy('position')
                ->first();

            $groupId = $group->id ?? DB::table('navigation_groups')->insertGetId([
                'navigation_menu_id' => $menuId,
                'region' => 'bottom',
                'label_key' => 'Help center',
                'icon' => 'lifebuoy',
                'position' => 0,
                'enabled' => true,
                'source' => 'root',
                'overridden' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $position = (int) DB::table('navigation_items')
                ->where('navigation_group_id', $groupId)
                ->max('position');

            DB::table('navigation_items')->insert([
                'navigation_group_id' => $groupId,
                'type' => 'route',
                'route_name' => 'help.index',
                'icon' => 'lifebuoy',
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
     * Remove only the backfilled help items and the groups this migration created.
     */
    public function down(): void
    {
        $itemIds = DB::table('navigation_items')
            ->where('route_name', 'help.index')
            ->pluck('id');

        $groupIds = DB::table('navigation_items')
            ->whereIn('id', $itemIds)
            ->pluck('navigation_group_id');

        DB::table('navigation_items')->whereIn('id', $itemIds)->delete();

        DB::table('navigation_groups')
            ->whereIn('id', $groupIds)
            ->where('label_key', 'Help center')
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))
                ->from('navigation_items')
                ->whereColumn('navigation_items.navigation_group_id', 'navigation_groups.id'))
            ->delete();

        NavigationCache::flush();
    }
};
