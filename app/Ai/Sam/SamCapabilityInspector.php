<?php

namespace App\Ai\Sam;

use App\Ai\Sam\Tools\SamProposeTool;
use App\Ai\Sam\Tools\SamReadTool;
use InvalidArgumentException;
use Laravel\Mcp\Server\Tool as McpTool;

/**
 * Static inspection of a capability and its handlers.
 *
 * It never invokes a tool and never asks the database: it only answers what a
 * module declared, which is what `sam:describe` prints and what `sam:doctor`
 * uses to report drift before a deployment.
 */
final class SamCapabilityInspector
{
    public const TYPE_SAM_READ = 'sam read';

    public const TYPE_SAM_PROPOSE = 'sam propose';

    public const TYPE_MCP_WRITE = 'mcp write';

    public const TYPE_MCP_READ = 'mcp read';

    public const TYPE_OTHER = 'other';

    /**
     * Transport type of one handler class.
     */
    public function typeOf(string $handler): string
    {
        if (is_subclass_of($handler, SamReadTool::class)) {
            return self::TYPE_SAM_READ;
        }

        if (is_subclass_of($handler, SamProposeTool::class)) {
            return self::TYPE_SAM_PROPOSE;
        }

        if (! is_subclass_of($handler, McpTool::class)) {
            return self::TYPE_OTHER;
        }

        if (! method_exists($handler, 'declaration')) {
            return self::TYPE_MCP_READ;
        }

        // `declaration()` is only present on a write tool, and a readable
        // resource is exposed through a read tool, not through a write one.
        return self::TYPE_MCP_WRITE;
    }

    /**
     * Problems of one capability, as human readable sentences.
     *
     * An empty list means the declaration is consistent with its handlers.
     *
     * @return list<string>
     */
    public function problems(SamCapability $capability): array
    {
        $problems = [];

        if ($capability->handlers === []) {
            $problems[] = 'declares no handler';
        }

        $types = array_map($this->typeOf(...), $capability->handlers);

        $expected = $capability->kind === SamCapabilityKind::Read
            ? self::TYPE_SAM_READ
            : self::TYPE_SAM_PROPOSE;

        if ($capability->exposes('sam') && ! in_array($expected, $types, true)) {
            $problems[] = "exposes [sam] without a [{$expected}] handler";
        }

        foreach ($capability->handlers as $handler) {
            $type = $this->typeOf($handler);

            if ($type === self::TYPE_OTHER) {
                $problems[] = "the handler [{$handler}] is not a Sam or MCP tool";
            }

            if ($capability->kind === SamCapabilityKind::Read && $type === self::TYPE_SAM_PROPOSE) {
                $problems[] = "the read capability declares the proposal [{$handler}]";
            }

            if ($capability->kind === SamCapabilityKind::Propose && $type === self::TYPE_SAM_READ) {
                $problems[] = "the proposal declares the read tool [{$handler}]";
            }

            if ($type === self::TYPE_MCP_WRITE) {
                $problems = array_merge($problems, $this->writeToolProblems($capability, $handler));
            }
        }

        return array_values(array_unique($problems));
    }

    /**
     * Name, type and class of every handler, for `sam:describe`.
     *
     * @return list<array{class: string, type: string}>
     */
    public function handlers(SamCapability $capability): array
    {
        return array_map(
            fn (string $handler): array => [
                'class' => $handler,
                'type' => $this->typeOf($handler),
            ],
            $capability->handlers,
        );
    }

    /**
     * Matching problems between a capability and one MCP write handler.
     *
     * @return list<string>
     */
    private function writeToolProblems(SamCapability $capability, string $handler): array
    {
        $problems = [];

        if ($capability->kind === SamCapabilityKind::Read) {
            $problems[] = "the read capability declares the write tool [{$handler}]";

            return $problems;
        }

        if (! $capability->exposes('mcp')) {
            $problems[] = "the write tool [{$handler}] is declared but [mcp] is not an exposed surface";
        }

        try {
            $declaration = $handler::declaration();
        } catch (InvalidArgumentException $exception) {
            $problems[] = "the write tool [{$handler}] has no WritesData declaration: {$exception->getMessage()}";

            return $problems;
        }

        if ($declaration->permission !== $capability->permission) {
            $problems[] = sprintf(
                'the write tool [%s] declares the permission [%s] and the capability [%s]',
                $handler,
                $declaration->permission ?? 'null',
                $capability->permission ?? 'null',
            );
        }

        $risk = $this->declaredRisk($handler);

        if ($risk !== null && $risk !== $capability->risk) {
            $problems[] = sprintf(
                'the write tool [%s] declares the risk [%s] and the capability [%s]',
                $handler,
                $risk->value,
                $capability->risk->value,
            );
        }

        return $problems;
    }

    /**
     * Risk declared by the handler itself, when it still declares one.
     */
    private function declaredRisk(string $handler): ?SamRisk
    {
        $constant = $handler.'::RISK';

        if (! defined($constant)) {
            return null;
        }

        $risk = constant($constant);

        return $risk instanceof SamRisk ? $risk : null;
    }
}
