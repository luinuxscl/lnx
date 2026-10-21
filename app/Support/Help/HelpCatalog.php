<?php

namespace App\Support\Help;

use App\Models\HelpEntry;
use App\Models\HelpGroup;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Resolves the help center content that the application renders.
 *
 * Package declarations live in `HelpRegistry`; root's decisions live in the
 * `help_entries` / `help_groups` tables. A declared entry without a stored row
 * still renders (an install that has not run `help:sync` yet), a stored row
 * that root overrode wins over the declaration, and a stored row that root hid
 * never renders. Entries whose package disappeared are not rendered either:
 * synchronization keeps a hidden record instead of resurrecting them.
 */
class HelpCatalog
{
    public function __construct(private readonly HelpRegistry $registry) {}

    /**
     * Every entry the identity may read, in catalog order.
     *
     * @return list<array{source: string, slug: string, type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null}>
     */
    public function all(User $user): array
    {
        $entries = [];

        foreach ($this->resolved() as $entry) {
            if ($this->visible($entry, $user)) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * A readable entry by slug, or null when it does not exist, is restricted
     * or root hid it.
     *
     * @return array{source: string, slug: string, type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null}|null
     */
    public function find(string $slug, User $user): ?array
    {
        foreach ($this->all($user) as $entry) {
            if ($entry['slug'] === $slug) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Readable entries whose title, summary or body contain the term.
     *
     * @return list<array{source: string, slug: string, type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null}>
     */
    public function search(string $term, User $user): array
    {
        $term = Str::lower(trim($term));

        if ($term === '') {
            return $this->all($user);
        }

        return array_values(array_filter(
            $this->all($user),
            function (array $entry) use ($term): bool {
                $haystack = Str::lower(implode("\n", [
                    (string) __($entry['title']),
                    (string) __($entry['summary']),
                    (string) __($entry['body']),
                ]));

                return Str::contains($haystack, $term);
            },
        ));
    }

    /**
     * Render an entry body as safe Markdown in the current locale.
     *
     * @param  array{body: string}  $entry
     */
    public function bodyHtml(array $entry): string
    {
        return Str::markdown((string) __($entry['body']), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * The URL of the entry's optional feature route, when it exists.
     *
     * @param  array{route: string|null}  $entry
     */
    public function routeUrl(array $entry): ?string
    {
        return $entry['route'] !== null && Route::has($entry['route'])
            ? route($entry['route'])
            : null;
    }

    /**
     * Declared entries that have no stored row yet, offered to root as
     * "available" so they can be published before the next synchronization.
     *
     * @return list<array{source: string, slug: string, type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null}>
     */
    public function available(): array
    {
        $stored = HelpEntry::query()->pluck('slug')->all();

        return array_values(array_filter(
            $this->registry->declared(),
            fn (array $entry): bool => ! in_array($entry['slug'], $stored, true),
        ));
    }

    /**
     * Merge declarations with stored rows into renderable entries.
     *
     * @return list<array{source: string, slug: string, type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null}>
     */
    private function resolved(): array
    {
        /** @var array<string, HelpEntry> $rows */
        $rows = HelpEntry::query()->with('group')->get()->keyBy('slug')->all();
        /** @var array<string, HelpGroup> $groups */
        $groups = HelpGroup::query()->get()->keyBy(fn (HelpGroup $group): string => $this->groupIndex([
            'source' => $group->source,
            'group' => $group->group_key,
        ]))->all();
        $groupPositions = $this->declaredGroupPositions();
        $entries = [];

        foreach ($this->registry->declared() as $declaration) {
            $row = $rows[$declaration['slug']] ?? null;
            $storedGroup = $groups[$this->groupIndex($declaration)] ?? null;

            if ($row !== null && ! $row->enabled) {
                continue;
            }

            if ($storedGroup !== null && ! $storedGroup->enabled) {
                continue;
            }

            if ($row !== null && $row->overridden) {
                $declaration['title'] = $row->content('title');
                $declaration['summary'] = $row->content('summary');
                $declaration['body'] = $row->content('body');
                $declaration['roles'] = $row->roleList();
            }

            if ($row !== null) {
                $declaration['type'] = $row->type;
                $declaration['group'] = $row->group->group_key;
                $declaration['order'] = $row->position;
                $declaration['route'] = $row->route_name;
                $groupPosition = $row->group->position;
            } else {
                $groupPosition = $groupPositions[$this->groupIndex($declaration)] ?? 0;
            }

            $entries[] = $declaration + ['group_position' => $groupPosition];
        }

        usort($entries, fn (array $left, array $right): int => [
            $left['source'] === HelpRegistry::STARTERKIT_SOURCE ? 1 : 0,
            $left['group_position'],
            $left['order'],
            $left['slug'],
        ] <=> [
            $right['source'] === HelpRegistry::STARTERKIT_SOURCE ? 1 : 0,
            $right['group_position'],
            $right['order'],
            $right['slug'],
        ]);

        return array_map(function (array $entry): array {
            unset($entry['group_position']);

            /** @var array{source: string, slug: string, type: string, group: string, title: string, summary: string, body: string, roles: list<string>, order: int, route: string|null} $entry */
            return $entry;
        }, $entries);
    }

    /**
     * First-appearance position of each declared group, used before the first
     * synchronization materializes groups in the database.
     *
     * @return array<string, int>
     */
    private function declaredGroupPositions(): array
    {
        $positions = [];

        foreach ($this->registry->declared() as $entry) {
            $index = $this->groupIndex($entry);

            if (! isset($positions[$index])) {
                $positions[$index] = count($positions);
            }
        }

        return $positions;
    }

    /**
     * @param  array{source: string, group: string}  $entry
     */
    private function groupIndex(array $entry): string
    {
        return $entry['source'].'|'.$entry['group'];
    }

    /**
     * @param  array{roles: list<string>}  $entry
     */
    private function visible(array $entry, User $user): bool
    {
        return $this->registry->visible($entry, $user);
    }
}
