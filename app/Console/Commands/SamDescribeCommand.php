<?php

namespace App\Console\Commands;

use App\Ai\Sam\SamCapability;
use App\Ai\Sam\SamCapabilityInspector;
use App\Ai\Sam\SamRegistry;
use Illuminate\Console\Command;

/**
 * Detail of one declared capability and the tools that implement it.
 *
 * Read-only: it never resolves an actor nor calls a tool.
 */
class SamDescribeCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'sam:describe
        {capability : Key of the capability, for example crm.archive_contact}
        {--json : Print the detail as JSON}';

    /**
     * @var string
     */
    protected $description = 'Describe one capability and the tools that implement it';

    public function handle(SamRegistry $registry, SamCapabilityInspector $inspector): int
    {
        $key = trim((string) $this->argument('capability'));
        $capability = $registry->capability($key);

        if (! $capability instanceof SamCapability) {
            $this->error("The capability [{$key}] is not declared.");

            return self::FAILURE;
        }

        $handlers = $inspector->handlers($capability);
        $problems = $inspector->problems($capability);

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'capability' => $capability->toArray(),
                'handlers' => $handlers,
                'problems' => $problems,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->render($capability, $handlers);

            foreach ($problems as $problem) {
                $this->error("DRIFT {$problem}");
            }
        }

        return $problems === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  list<array{class: string, type: string}>  $handlers
     */
    private function render(SamCapability $capability, array $handlers): void
    {
        $this->line("Key:           {$capability->key}");
        $this->line("Label:         {$capability->label}");
        $this->line("Module:        {$capability->source}");
        $this->line("Kind:          {$capability->kind->label()}");
        $this->line('Permission:    '.($capability->permission ?? '-'));
        $this->line('Risk:          '.($capability->risk?->label() ?? '-'));
        $this->line("Minimum mode:  {$capability->minMode->value}");
        $this->line("Confirmation:  {$capability->confirmation()}");
        $this->line('Undo:          '.($capability->undoable ? 'yes' : 'no'));
        $this->line("Audit action:  {$capability->auditAction}");
        $this->line('Surfaces:      '.implode(', ', $capability->surfaces));
        $this->newLine();

        if ($handlers === []) {
            $this->warn('No handler is declared for this capability.');

            return;
        }

        $this->table(
            ['Handler', 'Transport'],
            array_map(fn (array $handler): array => [$handler['class'], $handler['type']], $handlers),
        );
    }
}
