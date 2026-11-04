<?php

namespace App\Console\Commands;

use App\Ai\Sam\SamRuntime;
use App\Models\AiChatMessage;
use App\Models\AiConversation;
use Illuminate\Console\Command;

class PruneSamConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sam:prune-conversations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Sam conversation messages older than the configured retention period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) config('lnx_sam.retention_days', 0);

        if ($days <= 0) {
            $this->warn('Sam retention is not set; nothing was pruned.');

            return self::SUCCESS;
        }

        $conversationIds = AiConversation::query()
            ->where('profile', SamRuntime::PROFILE)
            ->select('id');

        $deleted = AiChatMessage::query()
            ->whereIn('conversation_id', $conversationIds)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deleted} Sam message(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
