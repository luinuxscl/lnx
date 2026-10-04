<?php

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ProductHelpSearchTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Search curated product help documentation and user guide topics for lnx.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The topic or feature keyword to search help documentation for.')->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user();

        // Re-validate authorization at runtime execution
        if (! $user instanceof User) {
            return 'Unauthorized: User is not authenticated to access product help.';
        }

        $query = strtolower((string) ($request['query'] ?? ''));

        $docs = [
            'settings' => 'Settings allow updating account profile, security (passkeys, 2FA, session revocation), and appearance (language, timezone, theme, dark mode). Root users can access General Settings at /admin/settings.',
            'preferences' => 'User preferences (locale, timezone, theme, color scheme) are saved per user account and applied automatically across devices.',
            'users' => 'Root and admin users can manage user accounts and send email invitations at /admin/users. User accounts can be suspended or reactivated.',
            'activity' => 'Root users can view administrative audit activity history at /admin/activity with filters by actor, action, and date range.',
            'ai' => 'AI features are managed at /admin/ai (root only). OpenRouter is the LLM provider. Usage limits and quotas are tracked at /admin/ai/usage.',
            'mcp' => 'Read-only MCP server can be configured at config/mcp_server.php. Bearer tokens for MCP access are managed in Settings -> Security.',
            'notifications' => 'The notification bell in the header opens /notifications. Administrative role changes produce persistent in-app notifications.',
        ];

        $results = [];

        foreach ($docs as $topic => $content) {
            if ($query === '' || str_contains(strtolower($topic), $query) || str_contains(strtolower($content), $query)) {
                $results[] = strtoupper($topic).': '.$content;
            }
        }

        if ($results === []) {
            return 'No specific help section found matching: '.$query.'. Provide general navigation guidance to /dashboard or /admin/settings.';
        }

        return implode("\n\n", array_slice($results, 0, 3));
    }
}
