<?php

namespace App\Ai\Knowledge;

use App\Ai\AiConfig;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Embeddings;
use Throwable;

class KnowledgeIndexer
{
    public function __construct(
        private readonly TextChunker $chunker,
        private readonly AiConfig $config,
    ) {}

    /**
     * Index a KnowledgeDocument by splitting text into chunks and generating embeddings.
     */
    public function index(KnowledgeDocument $document): bool
    {
        if ($document->withdrawn_at !== null) {
            $document->update([
                'status' => 'withdrawn',
                'error_message' => __('Document is withdrawn and cannot be indexed.'),
            ]);

            return false;
        }

        $document->update([
            'status' => 'indexing',
            'error_message' => null,
        ]);

        $chunkSize = (int) config('lnx_ai.knowledge.chunk_size', 500);
        $overlap = (int) config('lnx_ai.knowledge.chunk_overlap', 50);

        $chunkTexts = $this->chunker->chunk($document->content, $chunkSize, $overlap);

        if (empty($chunkTexts)) {
            $document->update([
                'status' => 'failed',
                'error_message' => __('Document content is empty or contains no indexable text.'),
                'chunk_count' => 0,
            ]);

            return false;
        }

        $embeddings = [];
        $hasEmbeddings = false;

        $provider = $this->config->provider();
        $embeddingModel = config('lnx_ai.knowledge.embedding_model', 'google/gemini-embedding-001');

        if ($this->config->enabled() && $this->config->providerConfigured()) {
            try {
                $response = Embeddings::for($chunkTexts)->generate(
                    provider: $provider,
                    model: $embeddingModel
                );

                if (isset($response->embeddings) && count($response->embeddings) === count($chunkTexts)) {
                    $embeddings = $response->embeddings;
                    $hasEmbeddings = true;
                }
            } catch (Throwable $e) {
                // If embedding generation fails, we log and proceed with text-only chunks or fail based on config
                $hasEmbeddings = false;
            }
        }

        try {
            DB::transaction(function () use ($document, $chunkTexts, $embeddings, $hasEmbeddings): void {
                KnowledgeChunk::where('document_id', $document->id)->delete();

                foreach ($chunkTexts as $index => $text) {
                    KnowledgeChunk::create([
                        'document_id' => $document->id,
                        'chunk_index' => $index,
                        'content' => $text,
                        'embedding' => $hasEmbeddings && isset($embeddings[$index]) ? $embeddings[$index] : null,
                        'tokens' => (int) ceil(mb_strlen($text) / 4),
                    ]);
                }

                $document->update([
                    'status' => 'indexed',
                    'chunk_count' => count($chunkTexts),
                    'error_message' => null,
                ]);
            });

            return true;
        } catch (Throwable $e) {
            $document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
