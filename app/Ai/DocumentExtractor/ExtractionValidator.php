<?php

namespace App\Ai\DocumentExtractor;

class ExtractionValidator
{
    /**
     * Validate and structure raw LLM response string or array.
     *
     * @param  string|array<string, mixed>  $rawResponse
     * @return array{data: array<string, mixed>, validation_notes: array{has_warnings: bool, fields: array<string, string>}}
     */
    public function validate(string|array $rawResponse): array
    {
        $decoded = is_array($rawResponse) ? $rawResponse : $this->parseJson($rawResponse);

        if ($decoded === null) {
            return [
                'data' => SampleExtractionSchema::defaultStructure(),
                'validation_notes' => [
                    'has_warnings' => true,
                    'fields' => [
                        'schema' => 'invalid_json_format',
                    ],
                ],
            ];
        }

        $fields = [];
        $hasWarnings = false;

        $documentType = isset($decoded['document_type']) ? (string) $decoded['document_type'] : 'other';
        $documentNumber = isset($decoded['document_number']) && is_string($decoded['document_number']) ? trim($decoded['document_number']) : null;
        $date = isset($decoded['date']) && is_string($decoded['date']) ? trim($decoded['date']) : null;
        $issuerName = isset($decoded['issuer_name']) && is_string($decoded['issuer_name']) ? trim($decoded['issuer_name']) : null;
        $recipientName = isset($decoded['recipient_name']) && is_string($decoded['recipient_name']) ? trim($decoded['recipient_name']) : null;
        $totalAmount = isset($decoded['total_amount']) && is_numeric($decoded['total_amount']) ? (float) $decoded['total_amount'] : null;
        $currency = isset($decoded['currency']) && is_string($decoded['currency']) ? trim($decoded['currency']) : null;
        $summary = isset($decoded['summary']) && is_string($decoded['summary']) ? trim($decoded['summary']) : null;

        $lineItems = [];
        if (isset($decoded['line_items']) && is_array($decoded['line_items'])) {
            foreach ($decoded['line_items'] as $item) {
                if (is_array($item)) {
                    $lineItems[] = [
                        'description' => isset($item['description']) ? (string) $item['description'] : '',
                        'quantity' => isset($item['quantity']) && is_numeric($item['quantity']) ? (float) $item['quantity'] : null,
                        'price' => isset($item['price']) && is_numeric($item['price']) ? (float) $item['price'] : null,
                    ];
                }
            }
        }

        // Validate individual key fields
        if (empty($date)) {
            $fields['date'] = 'missing';
            $hasWarnings = true;
        } elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $fields['date'] = 'invalid_format';
            $hasWarnings = true;
        } else {
            $fields['date'] = 'valid';
        }

        if (empty($issuerName)) {
            $fields['issuer_name'] = 'missing';
            $hasWarnings = true;
        } else {
            $fields['issuer_name'] = 'valid';
        }

        if ($totalAmount === null) {
            $fields['total_amount'] = 'missing';
            $hasWarnings = true;
        } else {
            $fields['total_amount'] = 'valid';
        }

        if (empty($documentNumber)) {
            $fields['document_number'] = 'missing';
            $hasWarnings = true;
        } else {
            $fields['document_number'] = 'valid';
        }

        $data = [
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'date' => $date,
            'issuer_name' => $issuerName,
            'recipient_name' => $recipientName,
            'total_amount' => $totalAmount,
            'currency' => $currency,
            'line_items' => $lineItems,
            'summary' => $summary,
        ];

        return [
            'data' => $data,
            'validation_notes' => [
                'has_warnings' => $hasWarnings,
                'fields' => $fields,
            ],
        ];
    }

    /**
     * Clean and parse JSON response string.
     *
     * @return array<string, mixed>|null
     */
    protected function parseJson(string $raw): ?array
    {
        $clean = trim($raw);

        // Strip Markdown json code block fences if present
        if (str_starts_with($clean, '```json')) {
            $clean = substr($clean, 7);
        } elseif (str_starts_with($clean, '```')) {
            $clean = substr($clean, 3);
        }

        if (str_ends_with($clean, '```')) {
            $clean = substr($clean, 0, -3);
        }

        $clean = trim($clean);

        $decoded = json_decode($clean, true);

        return is_array($decoded) ? $decoded : null;
    }
}
