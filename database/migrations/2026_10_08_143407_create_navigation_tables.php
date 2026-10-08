<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->string('audience')->unique();
            $table->timestamps();
        });

        Schema::create('navigation_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('navigation_menu_id')->constrained()->cascadeOnDelete();
            $table->string('label_key')->nullable();
            $table->string('label_es')->nullable();
            $table->string('label_en')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['navigation_menu_id', 'position']);
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('navigation_group_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('route'); // route, link
            $table->string('route_name')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('label_key')->nullable();
            $table->string('label_es')->nullable();
            $table->string('label_en')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['navigation_group_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigation_groups');
        Schema::dropIfExists('navigation_menus');
    }
};
