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
        Schema::create('mcp_write_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->string('tool');
            $table->string('resource');
            $table->string('operation');
            $table->string('resource_id')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->string('request_hash')->nullable();
            $table->string('status');
            $table->json('diff')->nullable();
            $table->json('response')->nullable();
            $table->uuid('correlation_id')->index();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->unique(['tool', 'actor_id', 'idempotency_key']);
            $table->index(['recorded_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcp_write_operations');
    }
};
