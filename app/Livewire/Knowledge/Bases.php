<?php

namespace App\Livewire\Knowledge;

use App\Enums\KnowledgeAudience;
use App\Models\KnowledgeBase;
use App\Models\User;
use Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Bases extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $audience = 'user';

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('manageAny', KnowledgeBase::class);
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', KnowledgeBase::class);

        $this->reset(['editingId', 'name', 'description']);
        $this->audience = KnowledgeAudience::User->value;
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $base = KnowledgeBase::findOrFail($id);
        $this->authorize('update', $base);

        $this->editingId = $base->id;
        $this->name = $base->name;
        $this->description = (string) $base->description;
        $this->audience = $base->audience->value;
        $this->is_active = $base->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'audience' => ['required', Rule::enum(KnowledgeAudience::class)],
            'is_active' => ['boolean'],
        ]);

        $assignable = array_map(
            fn (KnowledgeAudience $audience): string => $audience->value,
            KnowledgeAudience::assignableBy($user),
        );

        if (! in_array($this->audience, $assignable, true)) {
            $this->addError('audience', __('You cannot assign this audience.'));

            return;
        }

        $attributes = [
            'name' => trim($this->name),
            'description' => trim($this->description) !== '' ? trim($this->description) : null,
            'audience' => $this->audience,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId !== null) {
            $base = KnowledgeBase::findOrFail($this->editingId);
            $this->authorize('update', $base);
            $base->update($attributes);

            Flux::toast(variant: 'success', text: __('Knowledge base updated.'));
        } else {
            $this->authorize('create', KnowledgeBase::class);

            KnowledgeBase::create($attributes + [
                'slug' => $this->uniqueSlug($attributes['name']),
                'created_by' => $user->id,
            ]);

            Flux::toast(variant: 'success', text: __('Knowledge base created.'));
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name', 'description']);
    }

    public function toggleActive(int $id): void
    {
        $base = KnowledgeBase::findOrFail($id);
        $this->authorize('update', $base);

        $base->update(['is_active' => ! $base->is_active]);

        Flux::toast(
            variant: 'success',
            text: $base->is_active ? __('Knowledge base activated.') : __('Knowledge base deactivated.'),
        );
    }

    public function delete(int $id): void
    {
        $base = KnowledgeBase::findOrFail($id);
        $this->authorize('delete', $base);

        $base->delete();

        Flux::toast(variant: 'success', text: __('Knowledge base deleted.'));
    }

    public function render(): View
    {
        $this->authorize('manageAny', KnowledgeBase::class);

        /** @var User $user */
        $user = auth()->user();

        $query = KnowledgeBase::query()->withCount('documents');

        if (trim($this->search) !== '') {
            $query->where('name', 'like', '%'.trim($this->search).'%');
        }

        return view('livewire.knowledge.bases', [
            'bases' => $query->orderBy('name')->paginate(10),
            'audiences' => KnowledgeAudience::assignableBy($user),
        ])->layout('layouts.app', [
            'breadcrumbs' => [['label' => __('Knowledge Base'), 'href' => route('knowledge.index')]],
        ]);
    }

    /**
     * Build a unique slug from the given name.
     */
    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'base';
        $slug = $base;
        $suffix = 2;

        while (KnowledgeBase::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
