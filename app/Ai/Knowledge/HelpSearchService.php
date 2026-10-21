<?php

namespace App\Ai\Knowledge;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\User;
use App\Support\Help\HelpCatalog;
use App\Support\Help\HelpKnowledgeSync;

/**
 * Semantic search over the help center content stored in Knowledge.
 *
 * Access is resolved twice: the document set is restricted to the slugs the
 * identity may read before scoring, and every result is checked again against
 * the live catalog afterwards. This keeps restricted articles out even if the
 * index is stale or a package changes its roles.
 */
class HelpSearchService
{
    public function __construct(
        private readonly KnowledgeSearchService $knowledge,
        private readonly HelpCatalog $catalog,
    ) {}

    /**
     * Whether the help knowledge base has been indexed at least once.
     */
    public function isAvailable(): bool
    {
        return KnowledgeBase::query()
            ->where('slug', HelpKnowledgeSync::BASE_SLUG)
            ->exists();
    }

    /**
     * @return list<array{slug: string, type: string, title: string, content: string, similarity: float, url: string}>
     */
    public function search(User $user, string $query, ?int $limit = null): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $limit ??= (int) config('lnx_ai.help.max_results', 3);

        $entries = [];
        foreach ($this->catalog->all($user) as $entry) {
            $entries[$entry['slug']] = $entry;
        }

        if ($entries === []) {
            return [];
        }

        $baseId = KnowledgeBase::query()
            ->where('slug', HelpKnowledgeSync::BASE_SLUG)
            ->value('id');

        if ($baseId === null) {
            return [];
        }

        $documents = KnowledgeDocument::query()
            ->where('knowledge_base_id', $baseId)
            ->where('source_type', 'help')
            ->where('locale', $this->locale())
            ->whereIn('help_slug', array_keys($entries))
            ->indexed()
            ->get(['id', 'help_slug']);

        if ($documents->isEmpty()) {
            return [];
        }

        $chunks = KnowledgeChunk::query()
            ->whereIn('document_id', $documents->pluck('id')->all())
            ->with(['document' => fn ($query) => $query->select('id', 'title', 'user_id', 'status', 'withdrawn_at')])
            ->get();

        $results = $this->knowledge->rankChunks(
            $chunks,
            $query,
            max($limit * 3, $limit),
        );

        $selected = [];

        foreach ($results as $result) {
            $documentId = (int) $result['document_id'];
            $document = $documents->firstWhere('id', $documentId);

            if ($document === null || ! is_string($document->help_slug)) {
                continue;
            }

            $slug = $document->help_slug;

            if (isset($selected[$slug])) {
                continue;
            }

            // Post-search authorization: never return a slug the live catalog
            // no longer considers readable by this identity.
            $entry = $this->catalog->find($slug, $user);

            if ($entry === null) {
                continue;
            }

            $selected[$slug] = [
                'slug' => $slug,
                'type' => $entry['type'],
                'title' => $result['document_title'],
                'content' => $result['content'],
                'similarity' => $result['similarity'],
                'url' => route('help.index', ['topic' => $slug]),
            ];

            if (count($selected) >= $limit) {
                break;
            }
        }

        return array_values($selected);
    }

    private function locale(): string
    {
        return app()->getLocale() === 'en' ? 'en' : 'es';
    }
}
