<?php

namespace App\Policies;

use App\Models\McpToken;
use App\Models\User;

class McpTokenPolicy
{
    /**
     * Determine whether the user can revoke the access token.
     *
     * Only the owner may revoke a token; the root role bypasses this through
     * the global Gate::before hook.
     */
    public function revoke(User $user, McpToken $token): bool
    {
        return $user->is($token->user);
    }
}
