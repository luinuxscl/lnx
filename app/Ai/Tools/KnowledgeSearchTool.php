<?php

namespace App\Ai\Tools;

use App\Ai\Knowledge\KnowledgeSearchService;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class KnowledgeSearchTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Search authorized knowledge base documents and manuals for verifiable information.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The search query prompt or topic to search in the knowledge base.')->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user();

        // Re-validate authorization at runtime execution
        if (! $user instanceof User) {
            return 'Unauthorized: User is not authenticated.';
        }

        $query = trim((string) ($request['query'] ?? ''));
        if ($query === '') {
            return 'Query cannot be empty.';
        }

        $searchService = app(KnowledgeSearchService::class);
        $results = $searchService->search($user, $query, limit: 5);

        if ($results === []) {
            return 'No relevant authorized knowledge base documents found matching query: '.$query;
        }

        $output = [];
        foreach ($results as $index => $item) {
            $num = $index + 1;
            $output[] = "[Source {$num}: {$item['document_title']} (Chunk #{$item['chunk_index']})]\n{$item['content']}";
        }

        return implode("\n\n---\n\n", $output);
    }
}
