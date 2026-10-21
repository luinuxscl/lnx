<?php

namespace App\Ai\Knowledge;

use App\Ai\AiConfig;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Embeddings;
use Throwable;

class KnowledgeSearchService
{
    public function __construct(
        private readonly AiConfig $config,
    ) {}

    /**
     * Whether Knowledge Base feature is configured and enabled.
     */
    public function isReady(): bool
    {
        return $this->config->enabled() && (bool) config('lnx_ai.knowledge.enabled', false);
    }

    /**
     * The knowledge bases a user is allowed to use.
     *
     * When requested ids are given, they are intersected with the bases the
     * user can actually see, so a client can never widen its own scope.
     *
     * @param  array<int, int>|null  $requestedIds
     * @return Collection<int, KnowledgeBase>
     */
    public function usableBases(User $user, ?array $requestedIds = null): Collection
    {
        $query = KnowledgeBase::query()->visibleTo($user);

        if ($requestedIds !== null) {
            $query->whereIn('id', array_map('intval', $requestedIds));
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Search relevant authorized knowledge chunks for a given prompt and user.
     *
     * When `$knowledgeBaseIds` is null the search spans every document the
     * user can access (legacy behavior). When an array is given, the search is
     * limited to those bases, intersected with the ones the user may use.
     *
     * @param  array<int, int>|null  $knowledgeBaseIds
     * @return list<array{chunk_id: int, document_id: int, document_title: string, chunk_index: int, content: string, similarity: float}>
     */
    public function search(User $user, string $queryPrompt, ?array $knowledgeBaseIds = null, ?int $limit = null): array
    {
        $queryPrompt = trim($queryPrompt);
        if ($queryPrompt === '') {
            return [];
        }

        $limit ??= (int) config('lnx_ai.knowledge.max_chunks', 5);

        // 1. Authorization-first: query ONLY accessible and indexed documents
        $documentQuery = KnowledgeDocument::query()
            ->accessibleTo($user)
            ->indexed();

        if ($knowledgeBaseIds !== null) {
            $effectiveBaseIds = $this->usableBases($user, $knowledgeBaseIds)->pluck('id')->all();

            if ($effectiveBaseIds === []) {
                return [];
            }

            $documentQuery->inBases($effectiveBaseIds);
        }

        $accessibleDocumentIds = $documentQuery->pluck('id')->all();

        if (empty($accessibleDocumentIds)) {
            return [];
        }

        // 2. Fetch accessible chunks
        $chunks = KnowledgeChunk::query()
            ->whereIn('document_id', $accessibleDocumentIds)
            ->with(['document' => fn ($q) => $q->select('id', 'title', 'user_id', 'status', 'withdrawn_at')])
            ->get();

        return $this->rankChunks($chunks, $queryPrompt, $limit);
    }

    /**
     * Rank a pre-authorized set of chunks against a query.
     *
     * The caller is responsible for resolving the chunks from documents the
     * identity may read. This is the shared scoring engine used by the regular
     * knowledge search and by the help center search, so both keep the same
     * vector and textual fallback behavior.
     *
     * @param  Collection<int, KnowledgeChunk>  $chunks
     * @return list<array{chunk_id: int, document_id: int, document_title: string, chunk_index: int, content: string, similarity: float}>
     */
    public function rankChunks(Collection $chunks, string $queryPrompt, ?int $limit = null): array
    {
        $queryPrompt = trim($queryPrompt);

        if ($queryPrompt === '' || $chunks->isEmpty()) {
            return [];
        }

        $limit ??= (int) config('lnx_ai.knowledge.max_chunks', 5);
        $minSimilarity = (float) config('lnx_ai.knowledge.min_similarity', 0.15);

        $queryEmbedding = $this->generateQueryEmbedding($queryPrompt);
        $results = [];

        foreach ($chunks as $chunk) {
            if ($chunk->document === null || $chunk->document->withdrawn_at !== null) {
                continue;
            }

            $similarity = ! empty($queryEmbedding) && is_array($chunk->embedding)
                ? $this->cosineSimilarity($queryEmbedding, $chunk->embedding)
                : $this->keywordSimilarity($queryPrompt, $chunk->content);

            if ($similarity < $minSimilarity) {
                continue;
            }

            $results[] = [
                'chunk_id' => (int) $chunk->id,
                'document_id' => (int) $chunk->document_id,
                'document_title' => (string) $chunk->document->title,
                'chunk_index' => (int) $chunk->chunk_index,
                'content' => (string) $chunk->content,
                'similarity' => (float) round($similarity, 4),
            ];
        }

        usort($results, fn (array $left, array $right): int => $right['similarity'] <=> $left['similarity']);

        return array_slice($results, 0, $limit);
    }

    /**
     * Generate embedding vector for the search query prompt.
     *
     * @return list<float>
     */
    protected function generateQueryEmbedding(string $query): array
    {
        if (! $this->config->enabled() || ! $this->config->providerConfigured()) {
            return [];
        }

        $provider = $this->config->provider();
        $model = config('lnx_ai.knowledge.embedding_model', 'google/gemini-embedding-001');

        try {
            $response = Embeddings::for([$query])->generate(
                provider: $provider,
                model: $model
            );

            if (isset($response->embeddings[0])) {
                return array_values($response->embeddings[0]);
            }
        } catch (Throwable $e) {
            // Fallback to empty array
        }

        return [];
    }

    /**
     * Compute Cosine Similarity between two numerical vectors.
     *
     * @param  array<int, float>  $vecA
     * @param  array<int, float>  $vecB
     */
    public function cosineSimilarity(array $vecA, array $vecB): float
    {
        $countA = count($vecA);
        $countB = count($vecB);

        if ($countA === 0 || $countA !== $countB) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $countA; $i++) {
            $valA = (float) $vecA[$i];
            $valB = (float) $vecB[$i];

            $dotProduct += $valA * $valB;
            $normA += $valA * $valA;
            $normB += $valB * $valB;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Fallback keyword similarity metric based on term overlap.
     */
    public function keywordSimilarity(string $query, string $content): float
    {
        $queryWords = array_unique(array_filter(explode(' ', mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $query)))));
        if (empty($queryWords)) {
            return 0.0;
        }

        $contentLower = mb_strtolower($content);
        $matches = 0;

        foreach ($queryWords as $word) {
            if (mb_strlen($word) > 2 && mb_strpos($contentLower, $word) !== false) {
                $matches++;
            }
        }

        return $matches / count($queryWords);
    }
}
