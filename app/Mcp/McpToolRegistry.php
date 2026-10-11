<?php

namespace App\Mcp;

use App\Mcp\Attributes\WritesData;
use App\Mcp\Servers\LnxServer;
use InvalidArgumentException;
use Laravel\Mcp\Server\Tool;

/**
 * In-memory registry of the tools declared by installed packages.
 *
 * A package's service provider registers its own tools during boot; the kit
 * never exposes a tool just because a package sits in `vendor/`. Registered
 * tools are merged into {@see LnxServer} at discovery time,
 * so a package adds capabilities without the kit keeping a fixed list. A tool
 * that writes must still declare {@see WritesData}; the
 * declaration is enforced when it runs and by `mcp:verify-writes`.
 */
class McpToolRegistry
{
    /**
     * Registered tool class names, keyed by the package source that declared them.
     *
     * @var array<string, list<class-string<Tool>>>
     */
    private array $tools = [];

    /**
     * Register the tools declared by a package.
     *
     * @param  array<array-key, mixed>  $tools
     *
     * @throws InvalidArgumentException when an entry is not an MCP tool class
     */
    public function register(string $source, array $tools): void
    {
        if ($source === '') {
            throw new InvalidArgumentException('The package source must be a non-empty string.');
        }

        foreach ($tools as $tool) {
            if (! is_string($tool) || ! is_subclass_of($tool, Tool::class)) {
                $name = is_string($tool) ? $tool : get_debug_type($tool);

                throw new InvalidArgumentException("Registered tool [{$name}] must extend ".Tool::class.'.');
            }

            $this->tools[$source] ??= [];

            if (! in_array($tool, $this->tools[$source], true)) {
                $this->tools[$source][] = $tool;
            }
        }
    }

    /**
     * Every registered tool class, deduplicated and in registration order.
     *
     * @return list<class-string<Tool>>
     */
    public function all(): array
    {
        $tools = [];

        foreach ($this->tools as $registered) {
            foreach ($registered as $tool) {
                if (! in_array($tool, $tools, true)) {
                    $tools[] = $tool;
                }
            }
        }

        return $tools;
    }
}
