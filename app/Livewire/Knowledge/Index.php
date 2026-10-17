<?php

namespace App\Livewire\Knowledge;

use App\Ai\Knowledge\KnowledgeIndexer;
use App\Jobs\IndexKnowledgeDocumentJob;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeDocument;
use App\Models\User;
use Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
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

    public ?int $baseId = null;

    public ?string $baseFilter = null;

    /**
     * Uploaded file draft.
     *
     * @var mixed
     */
    public $file;

    public bool $showCreateModal = false;

    public function openCreateModal(): void
    {
        $this->reset(['title', 'sourceType', 'content', 'file', 'baseId']);
        $this->baseId = $this->visibleBases()->first()?->id;
        $this->showCreateModal = true;
    }

    public function createDocument(KnowledgeIndexer $indexer): void
    {
        $this->authorize('create', KnowledgeDocument::class);

        $this->validate([
            'baseId' => ['nullable', 'integer'],
        ]);

        $baseId = $this->resolveBaseId();

        if ($this->sourceType === 'file') {
            $this->validate([
                'title' => ['required', 'string', 'max:255'],
                'file' => ['required', 'file', 'max:10240', 'mimes:txt,md,json,csv'],
            ]);

            $fileContent = file_get_contents($this->file->getRealPath());
            $path = $this->file->store('knowledge/'.auth()->id(), 'local');

            $document = KnowledgeDocument::create([
                'user_id' => auth()->id(),
                'knowledge_base_id' => $baseId,
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
                'knowledge_base_id' => $baseId,
                'title' => trim($this->title),
                'source_type' => 'text',
                'content' => trim($this->content),
                'status' => 'indexing',
            ]);
        }

        IndexKnowledgeDocumentJob::dispatch($document);

        $this->showCreateModal = false;
        $this->reset(['title', 'sourceType', 'content', 'file', 'baseId']);

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

        $query = KnowledgeDocument::accessibleTo($user)->with(['user', 'base']);

        if (! empty(trim($this->search))) {
            $searchTerm = '%'.trim($this->search).'%';
            $query->where('title', 'like', $searchTerm);
        }

        if ($this->baseFilter !== null && $this->baseFilter !== '') {
            $query->where('knowledge_base_id', (int) $this->baseFilter);
        }

        $documents = $query->latest()->paginate(10);

        return view('livewire.knowledge.index', [
            'documents' => $documents,
            'bases' => $this->visibleBases(),
        ])->layout('layouts.app');
    }

    /**
     * Resolve the base to attach to a new document, validating visibility.
     */
    protected function resolveBaseId(): ?int
    {
        if ($this->baseId === null) {
            return null;
        }

        return $this->visibleBases()
            ->firstWhere('id', $this->baseId)?->id;
    }

    /**
     * The knowledge bases the current user may use.
     *
     * @return Collection<int, KnowledgeBase>
     */
    protected function visibleBases(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return KnowledgeBase::query()->visibleTo($user)->orderBy('name')->get();
    }
}
