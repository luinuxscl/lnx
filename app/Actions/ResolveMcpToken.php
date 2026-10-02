<?php

namespace App\Actions;

use App\Models\McpToken;

class ResolveMcpToken
{
    /**
     * Resolve a token (including revoked or expired ones) from its plain text value.
     */
    public function handle(?string $plainText): ?McpToken
    {
        if ($plainText === null || $plainText === '') {
            return null;
        }

        return McpToken::query()
            ->with('user')
            ->where('token_hash', IssueMcpToken::hash($plainText))
            ->first();
    }
}
