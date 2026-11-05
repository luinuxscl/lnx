<?php

use App\Ai\Sam\SamTitle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_messages', function (Blueprint $table): void {
            $table->json('context')->nullable()->after('content');
        });

        $this->backfillSamTitles();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_messages', function (Blueprint $table): void {
            $table->dropColumn('context');
        });
    }

    /**
     * Give the conversations stored before multiple threads a title taken from
     * their first message, so the thread list does not show a generic label.
     */
    private function backfillSamTitles(): void
    {
        $conversations = DB::table('ai_conversations')
            ->where('profile', 'sam')
            ->where(function ($query): void {
                $query->whereNull('title')->orWhere('title', 'Sam');
            })
            ->pluck('id');

        foreach ($conversations as $conversationId) {
            $firstMessage = DB::table('ai_messages')
                ->where('conversation_id', $conversationId)
                ->where('role', 'user')
                ->orderBy('created_at')
                ->orderBy('id')
                ->value('content');

            $title = SamTitle::from(is_string($firstMessage) ? $firstMessage : null);

            if ($title === null) {
                continue;
            }

            DB::table('ai_conversations')
                ->where('id', $conversationId)
                ->update(['title' => $title, 'updated_at' => Carbon::now()]);
        }
    }
};
