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
        Schema::create('ai_usage_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('correlation_id')->index();
            $table->unsignedInteger('attempt')->default(1);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('application');
            $table->string('provider');
            $table->string('requested_model')->nullable();
            $table->string('effective_model')->nullable();
            $table->unsignedBigInteger('input_tokens')->nullable();
            $table->unsignedBigInteger('output_tokens')->nullable();
            $table->unsignedBigInteger('total_tokens')->nullable();
            $table->decimal('reported_cost', 14, 6)->nullable();
            $table->decimal('estimated_cost', 14, 6)->nullable();
            $table->string('cost_currency', 8)->nullable();
            $table->string('cost_source')->default('unknown');
            $table->string('pricing_version')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('status');
            $table->string('error_category')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['recorded_at']);
            $table->index(['user_id', 'recorded_at']);
            $table->index(['application', 'recorded_at']);
            $table->index(['effective_model', 'recorded_at']);
            $table->index(['status', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_events');
    }
};
