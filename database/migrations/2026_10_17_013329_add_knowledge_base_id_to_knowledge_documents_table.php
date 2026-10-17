<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the knowledge base relation and gives every existing document a
     * home in a default "General" base, so no document is left without a
     * base after the upgrade. Access is now governed by the base audience.
     */
    public function up(): void
    {
        Schema::table('knowledge_documents', function (Blueprint $table): void {
            $table->foreignId('knowledge_base_id')
                ->nullable()
                ->after('user_id')
                ->constrained('knowledge_bases')
                ->nullOnDelete();
        });

        if (! Schema::hasTable('knowledge_documents') || ! Schema::hasTable('knowledge_bases')) {
            return;
        }

        if (! DB::table('knowledge_documents')->exists()) {
            return;
        }

        $baseId = DB::table('knowledge_bases')->where('slug', 'general')->value('id');

        if ($baseId === null) {
            $baseId = DB::table('knowledge_bases')->insertGetId([
                'name' => 'General',
                'slug' => 'general',
                'description' => 'Default shared knowledge base.',
                'audience' => 'user',
                'is_active' => true,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('knowledge_documents')
            ->whereNull('knowledge_base_id')
            ->update(['knowledge_base_id' => $baseId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('knowledge_base_id');
        });
    }
};
