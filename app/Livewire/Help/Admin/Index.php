<?php

namespace App\Livewire\Help\Admin;

use App\Actions\HelpSync;
use App\Actions\RecordAdministrativeActivity;
use App\Enums\AdministrativeAction;
use App\Enums\Role;
use App\Models\HelpEntry;
use App\Models\HelpGroup;
use App\Support\Help\HelpCatalog;
use App\Support\Help\HelpKnowledgeSync;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Root's editorial view over the materialized help center.
 *
 * Packages remain the default source; root can correct copy, adjust roles,
 * reorder, hide or restore entries. Every mutation is stored as an override
 * so `help:sync` never overwrites it, and every change is audited.
 */
#[Title('Help center')]
class Index extends Component
{
    public const STATUSES = ['auto', 'overridden', 'available', 'hidden'];

    #[Url]
    public string $search = '';

    #[Url]
    public string $source = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $type = '';

    public bool $showEditor = false;

    public ?int $editingId = null;

    public string $editingSlug = '';

    public string $titleEs = '';

    public string $titleEn = '';

    public string $summaryEs = '';

    public string $summaryEn = '';

    public string $bodyEs = '';

    public string $bodyEn = '';

    /** @var list<string> */
    public array $roles = [];

    public function mount(): void
    {
        $this->authorizeRoot();

        if ($this->status !== '' && ! in_array($this->status, self::STATUSES, true)) {
            $this->status = '';
        }

        if ($this->type !== '' && ! in_array($this->type, HelpEntry::TYPES, true)) {
            $this->type = '';
        }
    }

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
    }

    // ----- Entries -----

    public function openEditor(int $entryId): void
    {
        $this->authorizeRoot();

        $entry = $this->entryOrFail($entryId);

        $this->editingId = $entry->id;
        $this->editingSlug = $entry->slug;
        $this->titleEs = $entry->title_es ?? $this->translation($entry->title_key, 'es');
        $this->titleEn = $entry->title_en ?? $this->translation($entry->title_key, 'en');
        $this->summaryEs = $entry->summary_es ?? $this->translation($entry->summary_key, 'es');
        $this->summaryEn = $entry->summary_en ?? $this->translation($entry->summary_key, 'en');
        $this->bodyEs = $entry->body_es ?? $this->translation($entry->body_key, 'es');
        $this->bodyEn = $entry->body_en ?? $this->translation($entry->body_key, 'en');
        $this->roles = array_values(array_intersect($entry->roleList(), Role::assignableValues()));
        $this->resetValidation();
        $this->showEditor = true;
    }

    public function save(): void
    {
        $this->authorizeRoot();

        $data = $this->validate([
            'editingSlug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9][a-z0-9._-]*$/'],
            'titleEs' => ['required', 'string', 'max:255'],
            'titleEn' => ['required', 'string', 'max:255'],
            'summaryEs' => ['required', 'string', 'max:500'],
            'summaryEn' => ['required', 'string', 'max:500'],
            'bodyEs' => ['required', 'string', 'max:20000'],
            'bodyEn' => ['required', 'string', 'max:20000'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::in(Role::assignableValues())],
        ]);

        if ($this->editingId === null) {
            return;
        }

        $entry = $this->entryOrFail($this->editingId);
        $before = $this->snapshot($entry);

        $entry->update([
            'title_es' => $data['titleEs'],
            'title_en' => $data['titleEn'],
            'summary_es' => $data['summaryEs'],
            'summary_en' => $data['summaryEn'],
            'body_es' => $data['bodyEs'],
            'body_en' => $data['bodyEn'],
            'roles' => array_values($data['roles']),
            'enabled' => true,
            'overridden' => true,
        ]);

        $this->record(AdministrativeAction::HelpEntryUpdated, $entry, $before, $this->snapshot($entry));
        app(HelpKnowledgeSync::class)->syncEntry($entry->slug);
        $this->closeEditor();
        Flux::toast(variant: 'success', text: __('Entry updated.'));
    }

    public function toggleEntry(int $entryId): void
    {
        $this->authorizeRoot();

        $entry = $this->entryOrFail($entryId);
        $before = ['enabled' => $entry->enabled, 'overridden' => $entry->overridden];

        $entry->update(['enabled' => ! $entry->enabled, 'overridden' => true]);

        $this->record(
            $entry->enabled ? AdministrativeAction::HelpEntryUpdated : AdministrativeAction::HelpEntryHidden,
            $entry,
            $before,
            ['enabled' => $entry->enabled, 'overridden' => $entry->overridden],
        );

        app(HelpKnowledgeSync::class)->syncEntry($entry->slug);
    }

    public function restoreEntry(int $entryId): void
    {
        $this->authorizeRoot();

        app(HelpSync::class)->restoreEntry($this->entryOrFail($entryId));

        Flux::toast(variant: 'success', text: __('Entry restored to the package default.'));
    }

    public function publishEntry(string $slug): void
    {
        $this->authorizeRoot();

        $entry = app(HelpSync::class)->publish($slug, auth()->user());

        if ($entry === null) {
            Flux::toast(variant: 'danger', text: __('The entry could not be published.'));

            return;
        }

        Flux::toast(variant: 'success', text: __('Entry published.'));
    }

    public function moveEntry(int $entryId, string $direction): void
    {
        $this->authorizeRoot();

        $entry = $this->entryOrFail($entryId);
        $siblings = HelpEntry::query()
            ->where('help_group_id', $entry->help_group_id)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->values()
            ->all();

        $before = ['position' => $entry->position, 'overridden' => $entry->overridden];

        if (! $this->move($siblings, $entry, $direction)) {
            return;
        }

        foreach ($siblings as $position => $sibling) {
            $attributes = ['overridden' => true];

            if ($sibling->position !== $position) {
                $attributes['position'] = $position;
            }

            $sibling->update($attributes);
        }

        $this->record(AdministrativeAction::HelpEntryUpdated, $entry, $before, ['position' => $entry->position, 'overridden' => true]);
        Flux::toast(variant: 'success', text: __('Order updated.'));
    }

    public function moveGroup(int $groupId, string $direction): void
    {
        $this->authorizeRoot();

        $group = $this->groupOrFail($groupId);
        $siblings = HelpGroup::query()
            ->where('source', $group->source)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->values()
            ->all();

        $before = ['position' => $group->position, 'overridden' => $group->overridden];

        if (! $this->move($siblings, $group, $direction)) {
            return;
        }

        foreach ($siblings as $position => $sibling) {
            $sibling->update(['position' => $position, 'overridden' => true]);
        }

        $this->record(AdministrativeAction::HelpGroupUpdated, $group, $before, ['position' => $group->position, 'overridden' => true]);
        Flux::toast(variant: 'success', text: __('Order updated.'));
    }

    public function toggleGroup(int $groupId): void
    {
        $this->authorizeRoot();

        $group = $this->groupOrFail($groupId);
        $before = ['enabled' => $group->enabled, 'overridden' => $group->overridden];
        $group->update(['enabled' => ! $group->enabled, 'overridden' => true]);

        $this->record(AdministrativeAction::HelpGroupUpdated, $group, $before, ['enabled' => $group->enabled, 'overridden' => $group->overridden]);
        app(HelpKnowledgeSync::class)->syncAll();
    }

    public function restoreGroup(int $groupId): void
    {
        $this->authorizeRoot();

        app(HelpSync::class)->restoreGroup($this->groupOrFail($groupId));

        Flux::toast(variant: 'success', text: __('Group restored to the package default.'));
    }

    public function closeEditor(): void
    {
        $this->showEditor = false;
        $this->editingId = null;
        $this->editingSlug = '';
        $this->roles = [];
        $this->resetValidation();
    }

    public function render(): View
    {
        $this->authorizeRoot();

        $entries = $this->entries();
        $available = $this->available();

        return view('livewire.help.admin.index', [
            'entries' => $entries,
            'groups' => $this->grouped($entries),
            'available' => $available,
            'sources' => $this->sourceOptions(),
            'roleOptions' => Role::assignable(),
            'counts' => [
                'auto' => HelpEntry::query()->where('overridden', false)->where('enabled', true)->count(),
                'overridden' => HelpEntry::query()->where('overridden', true)->where('enabled', true)->count(),
                'hidden' => HelpEntry::query()->where('enabled', false)->count(),
                'available' => count($available),
            ],
        ])->layout('layouts.app', [
            'breadcrumbs' => [['label' => __('Help center')]],
        ]);
    }

    /**
     * @return Collection<int, HelpEntry>
     */
    private function entries(): Collection
    {
        if ($this->status === 'available') {
            return collect();
        }

        return HelpEntry::query()
            ->with('group')
            ->when($this->source !== '', fn (Builder $query) => $query->where('source', $this->source))
            ->when($this->type !== '', fn (Builder $query) => $query->where('type', $this->type))
            ->when($this->status === 'auto', fn (Builder $query) => $query->where('overridden', false)->where('enabled', true))
            ->when($this->status === 'overridden', fn (Builder $query) => $query->where('overridden', true)->where('enabled', true))
            ->when($this->status === 'hidden', fn (Builder $query) => $query->where('enabled', false))
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';

                $query->where(fn (Builder $query) => $query
                    ->where('slug', 'like', $term)
                    ->orWhere('title_key', 'like', $term)
                    ->orWhere('title_es', 'like', $term)
                    ->orWhere('title_en', 'like', $term));
            })
            ->orderBy('source')
            ->orderBy('position')
            ->get();
    }

    /**
     * @return list<array{source: string, slug: string, type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null}>
     */
    private function available(): array
    {
        if (in_array($this->status, ['auto', 'overridden', 'hidden'], true)) {
            return [];
        }

        $term = mb_strtolower($this->search);

        return array_values(array_filter(
            app(HelpCatalog::class)->available(),
            fn (array $entry): bool => ($this->source === '' || $entry['source'] === $this->source)
                && ($this->type === '' || $entry['type'] === $this->type)
                && ($term === ''
                    || str_contains(mb_strtolower($entry['slug']), $term)
                    || str_contains(mb_strtolower((string) __($entry['title'])), $term)),
        ));
    }

    /**
     * @param  Collection<int, HelpEntry>  $entries
     * @return Collection<string, Collection<int, HelpEntry>>
     */
    private function grouped(Collection $entries): Collection
    {
        return $entries->groupBy(fn (HelpEntry $entry): string => $entry->source.'|'.$entry->help_group_id);
    }

    /**
     * Every package that has stored entries or pending declarations, so the
     * filter keeps offering the other packages while one is selected.
     *
     * @return list<string>
     */
    private function sourceOptions(): array
    {
        $sources = array_map('strval', HelpEntry::query()->distinct()->pluck('source')->all());

        foreach (app(HelpCatalog::class)->available() as $entry) {
            $sources[] = $entry['source'];
        }

        $sources = array_values(array_unique($sources));
        sort($sources);

        return $sources;
    }

    /**
     * Swap a model with its neighbour inside an ordered list.
     *
     * @param  array<int, HelpEntry|HelpGroup>  $items
     */
    private function move(array &$items, HelpEntry|HelpGroup $model, string $direction): bool
    {
        $index = null;

        foreach ($items as $key => $item) {
            if ($item->is($model)) {
                $index = $key;
                break;
            }
        }

        if ($index === null) {
            return false;
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($target < 0 || $target >= count($items)) {
            return false;
        }

        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];

        return true;
    }

    private function translation(string $key, string $locale): string
    {
        return (string) trans($key, [], $locale);
    }

    private function entryOrFail(int $entryId): HelpEntry
    {
        return HelpEntry::query()->with('group')->findOrFail($entryId);
    }

    private function groupOrFail(int $groupId): HelpGroup
    {
        return HelpGroup::query()->findOrFail($groupId);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(HelpEntry $entry): array
    {
        return app(HelpSync::class)->entrySnapshot($entry);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    private function record(AdministrativeAction $action, HelpEntry|HelpGroup $resource, array $before = [], array $after = []): void
    {
        app(RecordAdministrativeActivity::class)->handle(auth()->user(), $action, $resource, $before, $after);
    }

    private function authorizeRoot(): void
    {
        abort_unless(auth()->user()?->hasRole(Role::Root), 403);
    }
}
