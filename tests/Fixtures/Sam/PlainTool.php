<?php

namespace Tests\Fixtures\Sam;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * A tool that is not declared with the kit bases: the runtime must reject it.
 */
class PlainTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Not a declared Sam tool.';
    }

    public function handle(Request $request): Stringable|string
    {
        return 'This should never run.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
