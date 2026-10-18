<?php

namespace App\Livewire\Knowledge;

use App\Jobs\IndexKnowledgeDocumentJob;
use App\Models\KnowledgeDocument;
use Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public KnowledgeDocument $document;

    public function mount(KnowledgeDocument $document): void
    {
        $this->authorize('view', $document);
        $this->document = $document->load(['user', 'chunks' => fn ($q) => $q->orderBy('chunk_index', 'asc')]);
    }

    public function reindex(): void
    {
        $this->authorize('reindex', $this->document);

        $this->document->update([
            'status' => 'indexing',
            'error_message' => null,
            'withdrawn_at' => null,
        ]);

        IndexKnowledgeDocumentJob::dispatch($this->document);

        Flux::toast(variant: 'success', text: __('Document re-indexing queued.'));
    }

    public function withdraw(): void
    {
        $this->authorize('withdraw', $this->document);

        $this->document->update([
            'status' => 'withdrawn',
            'withdrawn_at' => now(),
        ]);

        Flux::toast(variant: 'warning', text: __('Document withdrawn and excluded from knowledge searches.'));
    }

    public function render(): View
    {
        $this->authorize('view', $this->document);

        return view('livewire.knowledge.show', [
            'document' => $this->document,
        ])->layout('layouts.app', [
            'breadcrumbs' => [['label' => $this->document->title]],
        ]);
    }
}
