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
            $table->text('system_prompt')->nullable()->after('system_context');
            $table->foreignId('assistant_id')
                ->nullable()
                ->after('model')
                ->constrained('ai_assistants')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_conversations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assistant_id');
            $table->dropColumn('system_prompt');
        });
    }
};
