<?php

namespace App\Jobs;

use App\Ai\Knowledge\KnowledgeIndexer;
use App\Models\KnowledgeDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Throwable;

class IndexKnowledgeDocumentJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30];

    public function __construct(
        public readonly KnowledgeDocument $document,
    ) {}

    public function handle(KnowledgeIndexer $indexer): void
    {
        if ($this->document->withdrawn_at !== null) {
            return;
        }

        // Authenticate user in job context for quota and usage tracking
        if ($this->document->user) {
            Auth::setUser($this->document->user);
        }

        try {
            $indexer->index($this->document);
        } catch (Throwable $e) {
            $this->document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }
}
