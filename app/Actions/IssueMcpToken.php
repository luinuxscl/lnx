<?php

namespace App\Actions;

use App\Models\McpToken;
use App\Models\User;
use Illuminate\Support\Str;

class IssueMcpToken
{
    public const PREFIX = 'lnx_mcp_';

    /**
     * Issue a new access token for the given user.
     *
     * The plain text token is returned exactly once; only its hash is stored.
     *
     * @return array{token: McpToken, plain_text: string}
     */
    public function handle(User $user, string $name): array
    {
        $plainText = self::PREFIX.Str::random(48);
        $expiresDays = config('mcp_server.token.expires_days');

        $token = McpToken::create([
            'user_id' => $user->id,
            'name' => trim($name),
            'token_hash' => self::hash($plainText),
            'expires_at' => is_int($expiresDays) ? now()->addDays($expiresDays) : null,
        ]);

        return ['token' => $token, 'plain_text' => $plainText];
    }

    public static function hash(string $plainText): string
    {
        return hash('sha256', $plainText);
    }
}
