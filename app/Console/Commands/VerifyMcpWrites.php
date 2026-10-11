<?php

namespace App\Console\Commands;

use App\Mcp\McpToolRegistry;
use App\Mcp\Tools\WriteTool;
use Illuminate\Console\Command;
use InvalidArgumentException;

class VerifyMcpWrites extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:verify-writes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that every registered MCP write tool declares its writes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $failures = 0;
        $checked = 0;

        foreach (app(McpToolRegistry::class)->all() as $class) {
            if (! is_subclass_of($class, WriteTool::class)) {
                continue;
            }

            $checked++;

            try {
                $declaration = $class::declaration();
                $this->line("OK  {$class} → {$declaration->operation} {$declaration->resource}");
            } catch (InvalidArgumentException $exception) {
                $failures++;
                $this->error("FAIL {$class}: {$exception->getMessage()}");
            }
        }

        if ($failures > 0) {
            $this->error("{$failures} write tool(s) are not declared.");

            return self::FAILURE;
        }

        $this->info("All {$checked} registered write tool(s) are declared.");

        return self::SUCCESS;
    }
}
