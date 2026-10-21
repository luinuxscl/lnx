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
        Schema::create('help_groups', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('group_key');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('enabled')->default(true);
            $table->boolean('overridden')->default(false);
            $table->timestamps();

            $table->unique(['source', 'group_key']);
            $table->index(['source', 'position']);
        });

        Schema::create('help_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_group_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->string('slug')->unique();
            $table->string('type')->default('article');
            $table->string('title_key');
            $table->string('summary_key');
            $table->text('body_key');
            $table->string('title_es')->nullable();
            $table->string('title_en')->nullable();
            $table->string('summary_es')->nullable();
            $table->string('summary_en')->nullable();
            $table->text('body_es')->nullable();
            $table->text('body_en')->nullable();
            $table->json('roles')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->string('route_name')->nullable();
            $table->boolean('enabled')->default(true);
            $table->boolean('overridden')->default(false);
            $table->timestamps();

            $table->index(['help_group_id', 'position']);
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_entries');
        Schema::dropIfExists('help_groups');
    }
};
