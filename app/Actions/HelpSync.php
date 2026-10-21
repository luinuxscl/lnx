<?php

namespace App\Actions;

use App\Enums\AdministrativeAction;
use App\Models\HelpEntry;
use App\Models\HelpGroup;
use App\Models\User;
use App\Support\Help\HelpRegistry;
use Illuminate\Support\Facades\DB;

/**
 * Materializes the help entries declared by packages into the database.
 *
 * The package proposes and root decides: declared content is written as
 * translation keys, while root's edits, hiding and reordering are stored as
 * overrides that later runs never overwrite. Entries whose declaration
 * disappears are hidden (never deleted), so a synchronization cannot
 * resurrect a decision root already made. Running it twice changes nothing.
 */
class HelpSync
{
    public function __construct(private readonly HelpRegistry $registry) {}

    /**
     * @return array{created: int, updated: int, hidden: int}
     */
    public function handle(): array
    {
        $created = 0;
        $updated = 0;
        $declared = $this->registry->declared();
        $groups = $this->groupPositions($declared);

        DB::transaction(function () use ($declared, $groups, &$created, &$updated): void {
            foreach ($declared as $entry) {
                $group = $this->ensureGroup($entry, $groups);
                $row = HelpEntry::query()->where('slug', $entry['slug'])->first();

                if ($row === null) {
                    $row = HelpEntry::create($this->attributes($entry) + [
                        'help_group_id' => $group->id,
                        'source' => $entry['source'],
                        'slug' => $entry['slug'],
                        'overridden' => false,
                    ]);

                    $this->record(AdministrativeAction::HelpEntryCreated, $row, [], $this->entrySnapshot($row));
                    $created++;

                    continue;
                }

                $attributes = $row->overridden
                    ? $this->catalogAttributes($entry)
                    : $this->attributes($entry);

                $attributes['help_group_id'] = $group->id;
                $changes = $this->changed($row, $attributes);

                if ($changes === []) {
                    continue;
                }

                $before = $row->only(array_keys($changes));
                $row->update($changes);
                $this->record(AdministrativeAction::HelpEntryUpdated, $row, $before, $row->only(array_keys($changes)));
                $updated++;
            }
        });

        $hidden = $this->hideOrphans(array_column($declared, 'slug'));

        return ['created' => $created, 'updated' => $updated, 'hidden' => $hidden];
    }

    /**
     * Publish one declared entry before the next full synchronization.
     */
    public function publish(string $slug, ?User $actor = null): ?HelpEntry
    {
        if (HelpEntry::query()->where('slug', $slug)->exists()) {
            return null;
        }

        $entry = $this->declaration($slug);

        if ($entry === null) {
            return null;
        }

        $group = $this->ensureGroup($entry, $this->groupPositions($this->registry->declared()));

        $row = HelpEntry::create($this->attributes($entry) + [
            'help_group_id' => $group->id,
            'source' => $entry['source'],
            'slug' => $entry['slug'],
            'overridden' => false,
        ]);

        $this->record(AdministrativeAction::HelpEntryCreated, $row, [], $this->entrySnapshot($row), $actor);

        return $row;
    }

    /**
     * Clear root's override on an entry and return it to the package default.
     */
    public function restoreEntry(HelpEntry $entry): void
    {
        $entry->loadMissing('group');
        $declaration = $this->declaration($entry->slug);

        if ($declaration === null) {
            return;
        }

        $before = $this->entrySnapshot($entry);
        $group = $this->ensureGroup($declaration, $this->groupPositions($this->registry->declared()));

        $entry->update([
            ...$this->attributes($declaration),
            'help_group_id' => $group->id,
            ...$this->clearedOverrides(),
            'enabled' => true,
            'overridden' => false,
        ]);

        $this->record(AdministrativeAction::HelpEntryUpdated, $entry, $before, $this->entrySnapshot($entry));
    }

    /**
     * Clear root's override on a group and return it to its declared position.
     */
    public function restoreGroup(HelpGroup $group): void
    {
        $position = $this->groupPositions($this->registry->declared())[$this->groupIndex($group->source, $group->group_key)] ?? null;

        $before = $this->groupSnapshot($group);
        $group->update([
            'position' => $position ?? $group->position,
            'enabled' => true,
            'overridden' => false,
        ]);

        $this->record(AdministrativeAction::HelpGroupUpdated, $group, $before, $this->groupSnapshot($group));
    }

    /**
     * @return array<int, array{source: string, slug: string, type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null}>
     */
    private function declared(): array
    {
        return $this->registry->declared();
    }

    /**
     * @return array{source: string, slug: string, type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null}|null
     */
    private function declaration(string $slug): ?array
    {
        foreach ($this->declared() as $entry) {
            if ($entry['slug'] === $slug) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @param  array{source: string, group: string}  $entry
     * @param  array<string, int>  $positions
     */
    private function ensureGroup(array $entry, array $positions): HelpGroup
    {
        $index = $this->groupIndex($entry['source'], $entry['group']);

        $group = HelpGroup::query()
            ->where('source', $entry['source'])
            ->where('group_key', $entry['group'])
            ->first();

        if ($group !== null) {
            if (! $group->overridden && isset($positions[$index]) && $group->position !== $positions[$index]) {
                $group->update(['position' => $positions[$index]]);
            }

            return $group;
        }

        return HelpGroup::create([
            'source' => $entry['source'],
            'group_key' => $entry['group'],
            'position' => $positions[$index] ?? 0,
            'enabled' => true,
            'overridden' => false,
        ]);
    }

    /**
     * First-appearance position of every declared group, per source.
     *
     * @param  list<array{source: string, group: string}>  $declared
     * @return array<string, int>
     */
    private function groupPositions(array $declared): array
    {
        $positions = [];
        $counters = [];

        foreach ($declared as $entry) {
            $index = $this->groupIndex($entry['source'], $entry['group']);

            if (! isset($positions[$index])) {
                $positions[$index] = $counters[$entry['source']] ?? 0;
                $counters[$entry['source']] = $positions[$index] + 1;
            }
        }

        return $positions;
    }

    private function groupIndex(string $source, string $groupKey): string
    {
        return $source.'|'.$groupKey;
    }

    /**
     * Catalog-owned attributes, including content keys, visibility and order.
     *
     * @param  array{type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null}  $entry
     * @return array<string, mixed>
     */
    private function attributes(array $entry): array
    {
        return [
            ...$this->catalogAttributes($entry),
            'roles' => $entry['roles'],
            'position' => $entry['order'],
            'enabled' => true,
        ];
    }

    /**
     * Fields the package owns even when root overrode the entry: its content
     * keys and destination stay current so non-overridden fields keep
     * translating after a package update.
     *
     * @param  array{type: string, title: string, summary: string, body: string, route: string|null}  $entry
     * @return array<string, mixed>
     */
    private function catalogAttributes(array $entry): array
    {
        return [
            'type' => $entry['type'],
            'title_key' => $entry['title'],
            'summary_key' => $entry['summary'],
            'body_key' => $entry['body'],
            'route_name' => $entry['route'],
        ];
    }

    /**
     * @return array<string, null>
     */
    private function clearedOverrides(): array
    {
        return [
            'title_es' => null,
            'title_en' => null,
            'summary_es' => null,
            'summary_en' => null,
            'body_es' => null,
            'body_en' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function changed(HelpEntry $entry, array $attributes): array
    {
        $changes = [];

        foreach ($attributes as $field => $value) {
            if ($entry->getAttribute($field) !== $value) {
                $changes[$field] = $value;
            }
        }

        return $changes;
    }

    /**
     * Hide (never delete) stored entries whose declaration disappeared.
     *
     * @param  list<string>  $declaredSlugs
     */
    private function hideOrphans(array $declaredSlugs): int
    {
        $hidden = 0;

        HelpEntry::query()
            ->whereNotIn('slug', $declaredSlugs)
            ->where('enabled', true)
            ->get()
            ->each(function (HelpEntry $entry) use (&$hidden): void {
                $entry->update(['enabled' => false]);
                $this->record(
                    AdministrativeAction::HelpEntryHidden,
                    $entry,
                    ['enabled' => true],
                    ['enabled' => false],
                );
                $hidden++;
            });

        return $hidden;
    }

    /**
     * @return array<string, mixed>
     */
    public function entrySnapshot(HelpEntry $entry): array
    {
        return $entry->only([
            'source', 'slug', 'type',
            'title_key', 'summary_key', 'body_key',
            'title_es', 'title_en', 'summary_es', 'summary_en', 'body_es', 'body_en',
            'roles', 'position', 'route_name', 'enabled', 'overridden',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function groupSnapshot(HelpGroup $group): array
    {
        return $group->only(['source', 'group_key', 'position', 'enabled', 'overridden']);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    private function record(AdministrativeAction $action, HelpEntry|HelpGroup $resource, array $before = [], array $after = [], ?User $actor = null): void
    {
        app(RecordAdministrativeActivity::class)->handle($actor, $action, $resource, $before, $after);
    }
}
