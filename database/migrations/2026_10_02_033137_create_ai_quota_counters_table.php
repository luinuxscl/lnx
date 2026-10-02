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
        Schema::create('ai_quota_counters', function (Blueprint $table): void {
            $table->id();
            $table->string('scope_type');
            $table->string('scope_id');
            $table->string('window');
            $table->timestamp('window_start');
            $table->unsignedBigInteger('requests')->default(0);
            $table->unsignedBigInteger('tokens')->default(0);
            $table->unsignedBigInteger('reserved_tokens')->default(0);
            $table->timestamps();

            $table->unique(['scope_type', 'scope_id', 'window', 'window_start'], 'ai_quota_counters_scope_window_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_quota_counters');
    }
};
