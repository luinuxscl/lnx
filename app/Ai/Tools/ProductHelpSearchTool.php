<?php

namespace App\Ai\Tools;

use App\Models\User;
use App\Support\Help\HelpCatalog;
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

        $query = trim((string) ($request['query'] ?? ''));

        $entries = array_slice(app(HelpCatalog::class)->search($query, $user), 0, 3);

        if ($entries === []) {
            return 'No help section matched: '.$query.'. Point the user to the help center at '
                .route('help.index').' or offer to open a support request.';
        }

        $results = array_map(function (array $entry): string {
            $url = route('help.index', array_filter([
                'tab' => $entry['type'] === 'faq' ? 'faq' : 'guides',
                'topic' => $entry['slug'],
            ]));

            return __($entry['title'])."\n".__($entry['summary'])."\n".$url;
        }, $entries);

        return implode("\n\n", $results);
    }
}
