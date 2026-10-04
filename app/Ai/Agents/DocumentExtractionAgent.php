<?php

namespace App\Ai\Agents;

use App\Ai\DocumentExtractor\SampleExtractionSchema;
use App\Ai\LnxAgent;

/**
 * Agent responsible for extracting structured JSON data from document text.
 */
class DocumentExtractionAgent extends LnxAgent
{
    public function instructions(): string
    {
        return <<<'PROMPT'
        You are an expert document data extraction assistant.
        Your task is to analyze raw document text and extract structured information into JSON format.

        Rules:
        1. Extract only facts directly stated in the document text.
        2. Do not invent, hallucinate, or infer unmentioned dates, numbers, or names.
        3. If a field is not present in the document text, set its value to null.
        4. Return exclusively raw JSON matching the requested schema without Markdown code fences, comments or preamble.
        PROMPT;
    }

    public function promptInstructions(): string
    {
        return SampleExtractionSchema::promptInstructions();
    }

    public function maxTokens(): int
    {
        return max(1, (int) config('lnx_ai.document_extraction.max_output_tokens', 2048));
    }

    public function timeout(): int
    {
        return max(1, (int) config('lnx_ai.document_extraction.max_timeout', 60));
    }
}
