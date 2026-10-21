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
        Schema::table('knowledge_documents', function (Blueprint $table): void {
            $table->string('help_slug')
                ->nullable()
                ->after('source_type')
                ->index();
            $table->string('locale', 5)
                ->nullable()
                ->after('help_slug');

            $table->unique(['help_slug', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_documents', function (Blueprint $table): void {
            $table->dropUnique(['help_slug', 'locale']);
            $table->dropIndex(['help_slug']);
            $table->dropColumn(['help_slug', 'locale']);
        });
    }
};
