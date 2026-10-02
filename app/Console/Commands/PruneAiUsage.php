<?php

namespace App\Console\Commands;

use App\Models\AiUsageEvent;
use Illuminate\Console\Command;

class PruneAiUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:prune-usage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete AI consumption records older than the configured retention period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) config('lnx_ai.usage.retention_days');

        if ($days <= 0) {
            $this->warn('AI usage retention is not set; nothing was pruned.');

            return self::SUCCESS;
        }

        $deleted = AiUsageEvent::query()
            ->where('recorded_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deleted} AI usage record(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
