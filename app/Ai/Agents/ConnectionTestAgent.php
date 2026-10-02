<?php

namespace App\Ai\Agents;

use App\Ai\LnxAgent;

/**
 * Minimal agent used by the explicit root connection test.
 *
 * Its output and timeout are capped independently of regular features so the
 * probe stays cheap.
 */
class ConnectionTestAgent extends LnxAgent
{
    public function instructions(): string
    {
        return 'You are a connectivity probe. Reply with a single short word to confirm the connection works.';
    }

    public function maxTokens(): int
    {
        return max(1, (int) config('lnx_ai.connection_test.max_output_tokens', 16));
    }

    public function timeout(): int
    {
        return max(1, (int) config('lnx_ai.connection_test.timeout', 15));
    }
}
