<?php

use App\Support\Navigation\NavigationCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split the managed navigation into regions.
     *
     * Every existing row stays in the `main` region, so an installation renders
     * exactly as before this migration; the curated `bottom` region is created
     * idempotently by `NavigationSeeder` (fresh installs and re-seeds alike).
     */
    public function up(): void
    {
        Schema::table('navigation_groups', function (Blueprint $table) {
            $table->string('region')->default('main')->after('navigation_menu_id');
            $table->index(['navigation_menu_id', 'region', 'position']);
        });

        NavigationCache::flush();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('navigation_groups', function (Blueprint $table) {
            $table->dropIndex(['navigation_menu_id', 'region', 'position']);
            $table->dropColumn('region');
        });
    }
};
