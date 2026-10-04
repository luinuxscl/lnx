<?php

namespace App\Jobs;

use App\Ai\Agents\DocumentExtractionAgent;
use App\Ai\AiErrorCategorizer;
use App\Ai\AiGateway;
use App\Ai\DocumentExtractor\Exceptions\UnprocessableDocumentException;
use App\Ai\DocumentExtractor\Exceptions\UnsupportedFileTypeException;
use App\Ai\DocumentExtractor\ExtractionValidator;
use App\Ai\DocumentExtractor\TextExtractor;
use App\Models\DocumentExtraction;
use App\Notifications\DocumentExtractionCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessDocumentExtractionJob implements ShouldQueue
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
        public readonly DocumentExtraction $document,
    ) {}

    public function handle(
        TextExtractor $extractor,
        AiGateway $gateway,
        ExtractionValidator $validator,
        AiErrorCategorizer $categorizer,
    ): void {
        // Do not re-process already confirmed documents
        if ($this->document->isConfirmed()) {
            return;
        }

        $this->document->update([
            'status' => DocumentExtraction::STATUS_PROCESSING,
            'error_message' => null,
        ]);

        try {
            $filePath = Storage::disk('local')->path($this->document->file_path);

            $extracted = $extractor->extract($filePath);

            $this->document->update([
                'extracted_text' => $extracted['text'],
                'page_count' => $extracted['page_count'],
            ]);

            // Authenticate user in job context for quota and usage tracking
            if ($this->document->user) {
                Auth::setUser($this->document->user);
            }

            $agent = new DocumentExtractionAgent;
            $prompt = $agent->promptInstructions()."\n\n<document_text>\n".$extracted['text']."\n</document_text>";
            $correlationId = (string) str()->uuid();

            $response = $gateway->prompt(
                $agent,
                $prompt,
                application: 'document_extraction',
                reservedTokens: $agent->maxTokens(),
                timeout: $agent->timeout(),
                correlationId: $correlationId,
            );

            $validationResult = $validator->validate($response->text);

            $this->document->update([
                'status' => DocumentExtraction::STATUS_REVIEW_REQUIRED,
                'extracted_data' => $validationResult['data'],
                'reviewed_data' => $validationResult['data'],
                'validation_notes' => $validationResult['validation_notes'],
                'correlation_id' => $correlationId,
                'processed_at' => now(),
            ]);

            $this->sendNotification('Extraction completed', 'Your document data is ready for review.');
        } catch (Throwable $exception) {
            $errorMessage = match (true) {
                $exception instanceof UnprocessableDocumentException,
                $exception instanceof UnsupportedFileTypeException => $exception->getMessage(),
                default => $categorizer->categorize($exception),
            };

            $this->document->update([
                'status' => DocumentExtraction::STATUS_FAILED,
                'error_message' => $errorMessage,
                'processed_at' => now(),
            ]);

            $this->sendNotification('Extraction failed', 'Failed to extract document: '.$errorMessage);

            // Re-throw exception if retries remain for non-domain errors
            if (! ($exception instanceof UnprocessableDocumentException || $exception instanceof UnsupportedFileTypeException) && $this->attempts() < $this->tries) {
                throw $exception;
            }
        }
    }

    /**
     * Send an optional database notification if the user supports notifications.
     */
    protected function sendNotification(string $title, string $message): void
    {
        // Check if application notification system or database notifications are active
        if ($this->document->user && method_exists($this->document->user, 'notify')) {
            try {
                // Silently attempt notification if notification class exists
                if (class_exists(DocumentExtractionCompletedNotification::class)) {
                    $this->document->user->notify(new DocumentExtractionCompletedNotification($this->document, $title, $message));
                }
            } catch (Throwable $e) {
                // Do not block extraction job on notification errors
            }
        }
    }
}
