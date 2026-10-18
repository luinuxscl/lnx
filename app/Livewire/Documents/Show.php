<?php

namespace App\Livewire\Documents;

use App\Ai\DocumentExtractor\SampleExtractionSchema;
use App\Events\DocumentExtractionConfirmed;
use App\Jobs\ProcessDocumentExtractionJob;
use App\Models\DocumentExtraction;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public DocumentExtraction $document;

    public string $document_type = 'other';

    public ?string $document_number = null;

    public ?string $date = null;

    public ?string $issuer_name = null;

    public ?string $recipient_name = null;

    public ?float $total_amount = null;

    public ?string $currency = null;

    public ?string $summary = null;

    /**
     * @var list<array{description: string, quantity: float|null, price: float|null}>
     */
    public array $line_items = [];

    public function mount(DocumentExtraction $document): void
    {
        $this->authorize('view', $document);

        $this->document = $document;
        $this->fillFromReviewedData();
    }

    /**
     * Load component state from reviewed_data or extracted_data.
     */
    protected function fillFromReviewedData(): void
    {
        $data = $this->document->reviewed_data ?? $this->document->extracted_data ?? SampleExtractionSchema::defaultStructure();

        $this->document_type = (string) ($data['document_type'] ?? 'other');
        $this->document_number = isset($data['document_number']) ? (string) $data['document_number'] : null;
        $this->date = isset($data['date']) ? (string) $data['date'] : null;
        $this->issuer_name = isset($data['issuer_name']) ? (string) $data['issuer_name'] : null;
        $this->recipient_name = isset($data['recipient_name']) ? (string) $data['recipient_name'] : null;
        $this->total_amount = isset($data['total_amount']) && is_numeric($data['total_amount']) ? (float) $data['total_amount'] : null;
        $this->currency = isset($data['currency']) ? (string) $data['currency'] : null;
        $this->summary = isset($data['summary']) ? (string) $data['summary'] : null;

        $this->line_items = [];
        if (isset($data['line_items']) && is_array($data['line_items'])) {
            foreach ($data['line_items'] as $item) {
                if (is_array($item)) {
                    $this->line_items[] = [
                        'description' => isset($item['description']) ? (string) $item['description'] : '',
                        'quantity' => isset($item['quantity']) && is_numeric($item['quantity']) ? (float) $item['quantity'] : null,
                        'price' => isset($item['price']) && is_numeric($item['price']) ? (float) $item['price'] : null,
                    ];
                }
            }
        }
    }

    /**
     * Build the structured array representation of current component fields.
     *
     * @return array<string, mixed>
     */
    protected function getReviewedDataArray(): array
    {
        return [
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'date' => $this->date,
            'issuer_name' => $this->issuer_name,
            'recipient_name' => $this->recipient_name,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'line_items' => $this->line_items,
            'summary' => $this->summary,
        ];
    }

    /**
     * Add an empty line item row.
     */
    public function addLineItem(): void
    {
        $this->line_items[] = [
            'description' => '',
            'quantity' => 1.0,
            'price' => 0.0,
        ];
    }

    /**
     * Remove a line item row.
     */
    public function removeLineItem(int $index): void
    {
        if (isset($this->line_items[$index])) {
            $lineItems = $this->line_items;
            unset($lineItems[$index]);
            $this->line_items = array_values($lineItems);
        }
    }

    /**
     * Save draft changes without confirming.
     */
    public function saveDraft(): void
    {
        $this->authorize('update', $this->document);

        $this->document->update([
            'reviewed_data' => $this->getReviewedDataArray(),
        ]);

        session()->flash('status', __('Draft changes saved successfully.'));
    }

    /**
     * Confirm document extraction and dispatch confirmation event.
     */
    public function confirm(): void
    {
        $this->authorize('confirm', $this->document);

        $this->validate([
            'document_type' => ['required', 'string'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'total_amount' => ['nullable', 'numeric'],
        ]);

        $reviewedData = $this->getReviewedDataArray();

        $this->document->update([
            'reviewed_data' => $reviewedData,
            'status' => DocumentExtraction::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        DocumentExtractionConfirmed::dispatch($this->document);

        session()->flash('status', __('Document confirmed successfully.'));
    }

    /**
     * Trigger re-processing.
     */
    public function reprocess(): void
    {
        $this->authorize('reprocess', $this->document);

        $this->document->update([
            'status' => DocumentExtraction::STATUS_PENDING,
            'error_message' => null,
        ]);

        ProcessDocumentExtractionJob::dispatch($this->document);

        session()->flash('status', __('Document processing re-queued.'));
    }

    public function render(): View
    {
        $this->authorize('view', $this->document);

        return view('livewire.documents.show', [
            'document' => $this->document,
        ])->layout('layouts.app', [
            'breadcrumbs' => [['label' => $this->document->original_filename]],
        ]);
    }
}
