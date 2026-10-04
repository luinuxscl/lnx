<?php

namespace App\Events;

use App\Models\DocumentExtraction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a user confirms a document extraction.
 */
class DocumentExtractionConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DocumentExtraction $document,
    ) {}
}
