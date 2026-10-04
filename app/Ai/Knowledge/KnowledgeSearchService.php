<?php

namespace App\Ai\Knowledge;

use App\Ai\AiConfig;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\User;
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
     * Search relevant authorized knowledge chunks for a given prompt and user.
     *
     * @return list<array{chunk_id: int, document_id: int, document_title: string, chunk_index: int, content: string, similarity: float}>
     */
    public function search(User $user, string $queryPrompt, ?int $limit = null): array
    {
        $queryPrompt = trim($queryPrompt);
        if ($queryPrompt === '') {
            return [];
        }

        $limit ??= (int) config('lnx_ai.knowledge.max_chunks', 5);
        $minSimilarity = (float) config('lnx_ai.knowledge.min_similarity', 0.15);

        // 1. Authorization-first: query ONLY accessible and indexed documents
        $accessibleDocumentIds = KnowledgeDocument::query()
            ->accessibleTo($user)
            ->indexed()
            ->pluck('id')
            ->all();

        if (empty($accessibleDocumentIds)) {
            return [];
        }

        // 2. Fetch accessible chunks
        $chunks = KnowledgeChunk::query()
            ->whereIn('document_id', $accessibleDocumentIds)
            ->with(['document' => fn ($q) => $q->select('id', 'title', 'user_id', 'status', 'withdrawn_at')])
            ->get();

        if ($chunks->isEmpty()) {
            return [];
        }

        // 3. Attempt vector embedding search
        $queryEmbedding = $this->generateQueryEmbedding($queryPrompt);

        $results = [];
        if (! empty($queryEmbedding)) {
            foreach ($chunks as $chunk) {
                // Ensure document is not withdrawn
                if ($chunk->document->withdrawn_at !== null) {
                    continue;
                }

                $similarity = 0.0;
                if (is_array($chunk->embedding)) {
                    $similarity = $this->cosineSimilarity($queryEmbedding, $chunk->embedding);
                } else {
                    $similarity = $this->keywordSimilarity($queryPrompt, $chunk->content);
                }

                if ($similarity >= $minSimilarity) {
                    $results[] = [
                        'chunk_id' => (int) $chunk->id,
                        'document_id' => (int) $chunk->document_id,
                        'document_title' => (string) $chunk->document->title,
                        'chunk_index' => (int) $chunk->chunk_index,
                        'content' => (string) $chunk->content,
                        'similarity' => (float) round($similarity, 4),
                    ];
                }
            }
        } else {
            // Fallback to keyword matching if embedding service is unavailable or turned off
            foreach ($chunks as $chunk) {
                if ($chunk->document->withdrawn_at !== null) {
                    continue;
                }

                $similarity = $this->keywordSimilarity($queryPrompt, $chunk->content);
                if ($similarity >= $minSimilarity) {
                    $results[] = [
                        'chunk_id' => (int) $chunk->id,
                        'document_id' => (int) $chunk->document_id,
                        'document_title' => (string) $chunk->document->title,
                        'chunk_index' => (int) $chunk->chunk_index,
                        'content' => (string) $chunk->content,
                        'similarity' => (float) round($similarity, 4),
                    ];
                }
            }
        }

        // Sort by similarity score descending
        usort($results, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

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
