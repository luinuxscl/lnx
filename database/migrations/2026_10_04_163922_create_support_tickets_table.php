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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('status', 30)->default('received');
            $table->string('priority', 20)->nullable();
            $table->string('title', 160);
            $table->text('description');
            $table->boolean('blocking')->nullable();
            $table->string('screen', 160)->nullable();
            $table->string('route_name', 160)->nullable();
            $table->string('app_version', 60)->nullable();
            $table->unsignedSmallInteger('window_width')->nullable();
            $table->unsignedSmallInteger('window_height')->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('theme', 20)->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->text('resolution')->nullable();
            $table->uuid('request_key')->unique();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
