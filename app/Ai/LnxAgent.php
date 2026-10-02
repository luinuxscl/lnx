<?php

namespace App\Ai;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/**
 * Base agent for lnx features.
 *
 * Provider, model, output cap and timeout come from configuration, so a
 * deployment can change them without touching code and the SDK never falls
 * back to a different provider or model on its own.
 */
abstract class LnxAgent implements Agent
{
    use Promptable;

    public function provider(): Lab
    {
        return app(AiConfig::class)->provider();
    }

    public function model(): string
    {
        return app(AiConfig::class)->defaultModel() ?? '';
    }

    public function maxTokens(): int
    {
        return app(AiConfig::class)->maxOutputTokens();
    }

    public function timeout(): int
    {
        return app(AiConfig::class)->timeout();
    }
}
