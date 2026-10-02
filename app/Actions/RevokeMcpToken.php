<?php

namespace App\Actions;

use App\Models\McpToken;
use App\Models\User;

class RevokeMcpToken
{
    /**
     * Revoke the given access token.
     *
     * Revocation is idempotent: revoking an already revoked token is a no-op.
     */
    public function handle(User $actor, McpToken $token): void
    {
        abort_unless($actor->can('revoke', $token), 403);

        if ($token->isRevoked()) {
            return;
        }

        $token->forceFill(['revoked_at' => now()])->save();
    }
}
