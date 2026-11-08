<?php

namespace App\Console\Commands;

use App\Ai\Sam\SamCapability;
use App\Ai\Sam\SamRegistry;
use Illuminate\Console\Command;

/**
 * Catalogue of the capabilities the installed modules declare for Sam.
 *
 * It is read-only and never touches the database: it prints what the modules
 * declared at boot, so it can run in CI before a deployment.
 */
class SamCapabilitiesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'sam:capabilities
        {--source= : Only the capabilities of this module}
        {--json : Print the catalogue as JSON}
        {--strict : Fail when an installed module declares no capability}';

    /**
     * @var string
     */
    protected $description = 'List the capabilities the installed modules declare for Sam';

    public function handle(SamRegistry $registry): int
    {
        $source = is_string($this->option('source')) ? trim((string) $this->option('source')) : '';
        $capabilities = $source !== ''
            ? $registry->capabilitiesFor($source)
            : $registry->capabilities();

        $pending = $this->pendingSources($registry, $source);

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'capabilities' => array_values(
                    array_map(fn (SamCapability $capability): array => $capability->toArray(), $capabilities)
                ),
                'pending' => $pending,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->render($capabilities);

            foreach ($pending as $declaring) {
                $this->warn("PENDING {$declaring}: the module declares no capability manifest.");
            }
        }

        if ($this->option('strict') && $pending !== []) {
            $this->error('Some installed modules do not declare their capabilities.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Modules installed in the runtime without a capability manifest.
     *
     * @return list<string>
     */
    private function pendingSources(SamRegistry $registry, string $source): array
    {
        $sources = $source !== '' ? [$source] : array_keys($registry->contributors());

        return array_values(array_filter(
            array_map('strval', $sources),
            fn (string $declaring): bool => $registry->has($declaring) && ! $registry->declaresCapabilities($declaring),
        ));
    }

    /**
     * @param  array<string, SamCapability>  $capabilities
     */
    private function render(array $capabilities): void
    {
        if ($capabilities === []) {
            $this->info('No capability is declared.');

            return;
        }

        $this->table(
            ['Key', 'Kind', 'Permission', 'Risk', 'Mode', 'Confirmation', 'Surfaces'],
            array_map(fn (SamCapability $capability): array => [
                $capability->key,
                $capability->kind->label(),
                $capability->permission ?? '-',
                $capability->risk?->label() ?? '-',
                $capability->minMode->value,
                $capability->confirmation(),
                implode(',', $capability->surfaces),
            ], array_values($capabilities)),
        );

        $this->line(sprintf('%d capability(ies) declared by %d module(s).', count($capabilities), count(
            array_unique(array_map(fn (SamCapability $capability): string => $capability->source, $capabilities))
        )));
    }
}
