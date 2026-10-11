<?php

namespace App\Console\Commands;

use App\Models\McpToolInvocation;
use App\Models\McpWriteOperation;
use Illuminate\Console\Command;

class PruneMcpInvocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:prune-invocations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete MCP tool invocations and write operations older than the configured retention period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) config('mcp_server.audit.retention_days');

        if ($days <= 0) {
            $this->warn('MCP audit retention is not set; nothing was pruned.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $deleted = McpToolInvocation::query()
            ->where('recorded_at', '<', $cutoff)
            ->delete();

        $writes = McpWriteOperation::query()
            ->where('recorded_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} MCP tool invocation(s) older than {$days} day(s).");
        $this->info("Deleted {$writes} MCP write operation(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
