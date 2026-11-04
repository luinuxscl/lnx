<?php

namespace App\Ai\Sam;

use App\Ai\AiConfig;
use App\Ai\AiGateway;
use App\Models\User;

/**
 * Server-side gate of the agent surface.
 *
 * The switch is off by default, the permission is configurable (the product
 * package declares it), and the runtime is only ready when AI is configured
 * and the model declares tool support: without tools Sam cannot read or
 * propose anything.
 */
class SamAccess
{
    public function __construct(
        private readonly AiGateway $gateway,
        private readonly AiConfig $config,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('lnx_sam.enabled', false);
    }

    public function permission(): string
    {
        $permission = config('lnx_sam.permission', 'sam.use');

        return is_string($permission) && trim($permission) !== '' ? trim($permission) : 'sam.use';
    }

    public function canUse(?User $actor): bool
    {
        return $actor instanceof User && $actor->can($this->permission());
    }

    public function ready(): bool
    {
        return $this->enabled()
            && $this->gateway->isReady()
            && $this->config->supports($this->config->defaultModel(), 'tools');
    }
}
