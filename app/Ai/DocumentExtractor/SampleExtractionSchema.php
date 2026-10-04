<?php

namespace App\Ai\DocumentExtractor;

/**
 * Sample extraction schema for invoices, receipts and general business documents.
 * Projects using lnx can replace or extend this class or define custom schemas.
 */
class SampleExtractionSchema
{
    /**
     * Structure description sent to the LLM agent prompt.
     */
    public static function promptInstructions(): string
    {
        return <<<'INSTRUCTIONS'
        Extract data from the document text matching the JSON structure below.
        Return ONLY valid JSON matching this schema with no Markdown code fences, preamble or extra text.

        {
          "document_type": "invoice|receipt|contract|form|other",
          "document_number": "string or null",
          "date": "YYYY-MM-DD or null",
          "issuer_name": "string or null",
          "recipient_name": "string or null",
          "total_amount": number or null,
          "currency": "USD|EUR|CLP|etc or null",
          "line_items": [
            {
              "description": "string",
              "quantity": number or null,
              "price": number or null
            }
          ],
          "summary": "Short 1-2 sentence summary of document content"
        }
        INSTRUCTIONS;
    }

    /**
     * Default fallback empty structure.
     *
     * @return array<string, mixed>
     */
    public static function defaultStructure(): array
    {
        return [
            'document_type' => 'other',
            'document_number' => null,
            'date' => null,
            'issuer_name' => null,
            'recipient_name' => null,
            'total_amount' => null,
            'currency' => null,
            'line_items' => [],
            'summary' => null,
        ];
    }
}
