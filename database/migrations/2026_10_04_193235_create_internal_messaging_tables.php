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
        Schema::create('internal_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index(); // direct, group, announcement, support
            $table->string('title')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('support_ticket_id')->nullable()->unique()->constrained('support_tickets')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('internal_conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_conversation_id')->constrained('internal_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member'); // creator, member, recipient
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['internal_conversation_id', 'user_id'], 'conv_part_unique');
        });

        Schema::create('internal_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_conversation_id')->constrained('internal_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_type')->default('user'); // user, system, ai
            $table->string('sender_name')->nullable();
            $table->text('body');
            $table->string('request_key')->nullable()->index();
            $table->foreignId('support_message_id')->nullable()->constrained('support_messages')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_messages');
        Schema::dropIfExists('internal_conversation_participants');
        Schema::dropIfExists('internal_conversations');
    }
};
