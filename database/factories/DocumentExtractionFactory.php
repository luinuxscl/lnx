<?php

namespace Database\Factories;

use App\Models\DocumentExtraction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentExtraction>
 */
class DocumentExtractionFactory extends Factory
{
    protected $model = DocumentExtraction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'original_filename' => fake()->word().'.pdf',
            'file_path' => 'documents/test/'.fake()->uuid().'.pdf',
            'file_size' => fake()->numberBetween(1000, 500000),
            'mime_type' => 'application/pdf',
            'page_count' => fake()->numberBetween(1, 5),
            'extracted_text' => fake()->paragraph(),
            'status' => DocumentExtraction::STATUS_PENDING,
            'extracted_data' => null,
            'reviewed_data' => null,
            'validation_notes' => null,
            'error_message' => null,
            'correlation_id' => null,
            'processed_at' => null,
            'confirmed_at' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentExtraction::STATUS_PROCESSING,
        ]);
    }

    public function reviewRequired(): static
    {
        $data = [
            'document_type' => 'invoice',
            'document_number' => 'INV-1001',
            'date' => '2026-10-04',
            'issuer_name' => 'Acme Corp',
            'recipient_name' => 'Test Client',
            'total_amount' => 150.00,
            'currency' => 'USD',
            'line_items' => [
                ['description' => 'Service fee', 'quantity' => 1, 'price' => 150.00],
            ],
            'summary' => 'Sample invoice extraction',
        ];

        return $this->state(fn (array $attributes) => [
            'status' => DocumentExtraction::STATUS_REVIEW_REQUIRED,
            'extracted_data' => $data,
            'reviewed_data' => $data,
            'validation_notes' => [
                'has_warnings' => false,
                'fields' => [],
            ],
            'processed_at' => now(),
        ]);
    }

    public function confirmed(): static
    {
        return $this->reviewRequired()->state(fn (array $attributes) => [
            'status' => DocumentExtraction::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentExtraction::STATUS_FAILED,
            'error_message' => 'Scanned or unreadable document: OCR is not supported',
            'processed_at' => now(),
        ]);
    }
}
