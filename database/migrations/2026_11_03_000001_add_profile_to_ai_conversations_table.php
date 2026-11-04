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
        Schema::table('ai_conversations', function (Blueprint $table): void {
            $table->string('profile')->default('chat')->after('user_id');
            $table->index(['user_id', 'profile', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_conversations', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'profile', 'updated_at']);
            $table->dropColumn('profile');
        });
    }
};
