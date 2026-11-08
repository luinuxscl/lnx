<?php

namespace App\Console\Commands;

use App\Ai\Sam\SamCapability;
use App\Ai\Sam\SamCapabilityInspector;
use App\Ai\Sam\SamRegistry;
use App\Ai\Sam\Tools\SamProposeTool;
use App\Ai\Sam\Tools\SamReadTool;
use App\Mcp\McpToolRegistry;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Detects drift between what the modules declare and what they implement.
 *
 * It is the command meant for CI: it exits non-zero when a capability has no
 * handler, when a write tool is exposed without a capability, when an MCP
 * declaration contradicts the capability, or when a module still has no
 * manifest after migration.
 */
class SamDoctorCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'sam:doctor
        {--actor= : Email or id used to resolve the tools exposed to one identity}';

    /**
     * @var string
     */
    protected $description = 'Report drift between the declared capabilities and the implemented tools';

    private int $failures = 0;

    private int $warnings = 0;

    public function handle(SamRegistry $registry, SamCapabilityInspector $inspector): int
    {
        $capabilities = $registry->capabilities();

        foreach ($registry->contributors() as $source => $contributor) {
            if (! $registry->declaresCapabilities($source)) {
                $this->warn("PENDING {$source}: the module declares no capability manifest.");
            }
        }

        $implemented = [];

        foreach ($capabilities as $capability) {
            $this->checkCapability($capability, $inspector, $implemented);
        }

        $this->checkMcpTools($registry, $implemented);
        $this->checkSamTools($registry, $capabilities);

        if ($this->failures > 0) {
            $this->error("{$this->failures} problem(s) found and {$this->warnings} warning(s).");

            return self::FAILURE;
        }

        $this->info(sprintf(
            'No drift: %d capability(ies) checked, %d warning(s).',
            count($capabilities),
            $this->warnings,
        ));

        return self::SUCCESS;
    }

    /**
     * @param  array<class-string, string>  $implemented
     */
    private function checkCapability(
        SamCapability $capability,
        SamCapabilityInspector $inspector,
        array &$implemented,
    ): void {
        foreach ($capability->handlers as $handler) {
            $implemented[$handler] = $capability->key;
        }

        $problems = $inspector->problems($capability);

        if ($problems === []) {
            $this->line("OK    {$capability->key}");

            return;
        }

        foreach ($problems as $problem) {
            $this->failure($capability->key, $problem);
        }
    }

    /**
     * A registered MCP write tool must belong to a declared capability.
     *
     * @param  array<class-string, string>  $implemented
     */
    private function checkMcpTools(SamRegistry $registry, array $implemented): void
    {
        foreach (app(McpToolRegistry::class)->bySource() as $source => $tools) {
            $source = (string) $source;

            foreach ($tools as $tool) {
                if (! $this->writes($tool) || isset($implemented[$tool])) {
                    continue;
                }

                if ($registry->declaresCapabilities($source)) {
                    $this->failure($source, "the write tool [{$tool}] is registered without a capability");

                    continue;
                }

                $this->warn("PENDING {$source}: the write tool [{$tool}] has no capability manifest yet.");
            }
        }
    }

    /**
     * Sam only builds tools per identity, so this check needs an actor.
     *
     * @param  array<string, SamCapability>  $capabilities
     */
    private function checkSamTools(SamRegistry $registry, array $capabilities): void
    {
        $value = $this->option('actor');

        if (! is_string($value) || trim($value) === '') {
            if ($capabilities !== []) {
                $this->line('INFO  Pass --actor=<email|id> to check the Sam tools exposed to one identity.');
            }

            return;
        }

        $actor = $this->actor(trim($value));

        if (! $actor instanceof User) {
            return;
        }

        $declared = [];

        foreach ($capabilities as $capability) {
            foreach ($capability->handlers as $handler) {
                $declared[$handler] = $capability->key;
            }
        }

        foreach ($registry->contributors() as $source => $contributor) {
            $source = (string) $source;

            $tools = [
                ...$contributor->readTools($actor),
                ...$contributor->proposeTools($actor),
            ];

            $classes = [];

            foreach ($tools as $tool) {
                if (! $tool instanceof SamReadTool && ! $tool instanceof SamProposeTool) {
                    continue;
                }

                $class = $tool::class;
                $classes[] = $class;

                if (isset($declared[$class])) {
                    $this->line("OK    {$declared[$class]} exposes {$class} to {$actor->email}");

                    continue;
                }

                if ($registry->declaresCapabilities($source)) {
                    $this->failure($source, "the Sam tool [{$class}] is exposed without a capability");

                    continue;
                }

                $this->warn("PENDING {$source}: the Sam tool [{$class}] has no capability manifest yet.");
            }

            // A capability declared for the Sam surface must be reachable by
            // this identity; the runtime filters tools per actor.
            foreach ($capabilities as $capability) {
                if ($capability->source !== $source || ! $capability->exposes('sam')) {
                    continue;
                }

                $missing = array_diff($capability->handlers, $classes);

                if (count($missing) === count($capability->handlers)) {
                    $this->warn(
                        "WARN  {$capability->key}: no handler is exposed to {$actor->email} right now."
                    );
                }
            }
        }
    }

    private function writes(string $tool): bool
    {
        return method_exists($tool, 'declaration');
    }

    private function actor(string $value): ?User
    {
        $actor = User::query()
            ->where('email', $value)
            ->when(ctype_digit($value), fn ($query) => $query->orWhere('id', (int) $value))
            ->first();

        if (! $actor instanceof User) {
            $this->failure('actor', "the identity [{$value}] does not exist");

            return null;
        }

        return $actor;
    }

    private function failure(string $scope, string $problem): void
    {
        $this->failures++;
        $this->error("FAIL  {$scope}: {$problem}");
    }
}
