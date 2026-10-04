<?php

namespace App\Livewire\Knowledge;

use App\Ai\Knowledge\KnowledgeIndexer;
use App\Jobs\IndexKnowledgeDocumentJob;
use App\Models\KnowledgeDocument;
use App\Models\User;
use Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests, WithFileUploads, WithPagination;

    public string $search = '';

    public string $title = '';

    public string $sourceType = 'text'; // text, file

    public string $content = '';

    /**
     * Uploaded file draft.
     *
     * @var mixed
     */
    public $file;

    public bool $showCreateModal = false;

    public function openCreateModal(): void
    {
        $this->reset(['title', 'sourceType', 'content', 'file']);
        $this->showCreateModal = true;
    }

    public function createDocument(KnowledgeIndexer $indexer): void
    {
        $this->authorize('create', KnowledgeDocument::class);

        if ($this->sourceType === 'file') {
            $this->validate([
                'title' => ['required', 'string', 'max:255'],
                'file' => ['required', 'file', 'max:10240', 'mimes:txt,md,json,csv'],
            ]);

            $fileContent = file_get_contents($this->file->getRealPath());
            $path = $this->file->store('knowledge/'.auth()->id(), 'local');

            $document = KnowledgeDocument::create([
                'user_id' => auth()->id(),
                'title' => trim($this->title),
                'source_type' => 'file',
                'file_path' => $path,
                'content' => $fileContent,
                'status' => 'indexing',
            ]);
        } else {
            $this->validate([
                'title' => ['required', 'string', 'max:255'],
                'content' => ['required', 'string', 'min:10'],
            ]);

            $document = KnowledgeDocument::create([
                'user_id' => auth()->id(),
                'title' => trim($this->title),
                'source_type' => 'text',
                'content' => trim($this->content),
                'status' => 'indexing',
            ]);
        }

        IndexKnowledgeDocumentJob::dispatch($document);

        $this->showCreateModal = false;
        $this->reset(['title', 'sourceType', 'content', 'file']);

        Flux::toast(variant: 'success', text: __('Knowledge document created and queued for indexing.'));
    }

    public function reindex(KnowledgeDocument $document): void
    {
        $this->authorize('reindex', $document);

        $document->update([
            'status' => 'indexing',
            'error_message' => null,
            'withdrawn_at' => null,
        ]);

        IndexKnowledgeDocumentJob::dispatch($document);

        Flux::toast(variant: 'success', text: __('Document re-indexing queued.'));
    }

    public function withdraw(KnowledgeDocument $document): void
    {
        $this->authorize('withdraw', $document);

        $document->update([
            'status' => 'withdrawn',
            'withdrawn_at' => now(),
        ]);

        Flux::toast(variant: 'warning', text: __('Document withdrawn and excluded from knowledge searches.'));
    }

    public function delete(KnowledgeDocument $document): void
    {
        $this->authorize('delete', $document);

        $document->delete();

        Flux::toast(variant: 'success', text: __('Knowledge document deleted.'));
    }

    public function render(): View
    {
        $this->authorize('viewAny', KnowledgeDocument::class);

        /** @var User $user */
        $user = auth()->user();

        $query = KnowledgeDocument::accessibleTo($user)->with('user');

        if (! empty(trim($this->search))) {
            $searchTerm = '%'.trim($this->search).'%';
            $query->where('title', 'like', $searchTerm);
        }

        $documents = $query->latest()->paginate(10);

        return view('livewire.knowledge.index', [
            'documents' => $documents,
        ])->layout('layouts.app', [
            'breadcrumbs' => [
                ['label' => __('Knowledge Base')],
            ],
        ]);
    }
}
