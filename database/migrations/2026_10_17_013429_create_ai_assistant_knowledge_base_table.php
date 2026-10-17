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
        Schema::create('ai_assistant_knowledge_base', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assistant_id')->constrained('ai_assistants')->cascadeOnDelete();
            $table->foreignId('knowledge_base_id')->constrained('knowledge_bases')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['assistant_id', 'knowledge_base_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_assistant_knowledge_base');
    }
};
