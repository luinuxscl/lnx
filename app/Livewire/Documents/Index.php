<?php

namespace App\Livewire\Documents;

use App\Jobs\ProcessDocumentExtractionJob;
use App\Models\DocumentExtraction;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests, WithFileUploads, WithPagination;

    /**
     * Uploaded file draft.
     *
     * @var mixed
     */
    public $file;

    /**
     * Upload and enqueue document processing.
     */
    public function upload(): void
    {
        $this->authorize('create', DocumentExtraction::class);

        $maxKb = (int) config('lnx_ai.document_extraction.max_file_size_kb', 10240);

        $this->validate([
            'file' => [
                'required',
                'file',
                'max:'.$maxKb,
                'mimes:pdf,txt,md,json,csv',
            ],
        ]);

        $path = $this->file->store('documents/'.auth()->id(), 'local');

        $document = DocumentExtraction::create([
            'user_id' => auth()->id(),
            'original_filename' => $this->file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $this->file->getSize(),
            'mime_type' => $this->file->getClientMimeType() ?: 'application/octet-stream',
            'status' => DocumentExtraction::STATUS_PENDING,
        ]);

        ProcessDocumentExtractionJob::dispatch($document);

        $this->reset('file');

        session()->flash('status', __('Document uploaded successfully and queued for extraction.'));
    }

    /**
     * Re-enqueue extraction for a document.
     */
    public function reprocess(DocumentExtraction $document): void
    {
        $this->authorize('reprocess', $document);

        $document->update([
            'status' => DocumentExtraction::STATUS_PENDING,
            'error_message' => null,
        ]);

        ProcessDocumentExtractionJob::dispatch($document);

        session()->flash('status', __('Document processing re-queued.'));
    }

    /**
     * Delete a document extraction record and its file.
     */
    public function delete(DocumentExtraction $document): void
    {
        $this->authorize('delete', $document);

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        session()->flash('status', __('Document deleted.'));
    }

    public function render(): View
    {
        $this->authorize('viewAny', DocumentExtraction::class);

        $documents = DocumentExtraction::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('livewire.documents.index', [
            'documents' => $documents,
        ])->layout('layouts.app', [
            'breadcrumbs' => [
                ['label' => __('Document extraction')],
            ],
        ]);
    }
}
