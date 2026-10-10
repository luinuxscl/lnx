<?php

namespace App\Actions;

use App\Enums\NavigationAudience;
use App\Enums\NavigationItemType;
use App\Enums\NavigationSource;
use App\Models\NavigationGroup;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Support\Navigation\NavigationCache;
use App\Support\Navigation\NavigationCatalog;

/**
 * Reconciles the catalog (route tags) with the placements stored in the
 * database, per audience.
 *
 * It materializes autopublishable routes, follows the tag defaults for group,
 * order and icon, respects any root override and hides catalog items whose
 * route disappeared or lost its tag (root-authored items are never touched).
 * Running it twice is a no-op.
 */
class NavigationSync
{
    public function __construct(private readonly NavigationCatalog $catalog) {}

    /**
     * @return array<string, array{created: int, updated: int, hidden: int}>
     */
    public function handle(?NavigationAudience $audience = null): array
    {
        $audiences = $audience !== null ? [$audience] : NavigationAudience::cases();
        $summary = [];

        foreach ($audiences as $case) {
            $summary[$case->value] = $this->syncAudience($case);
        }

        NavigationCache::flush();

        return $summary;
    }

    /**
     * @return array{created: int, updated: int, hidden: int}
     */
    public function syncAudience(NavigationAudience $audience): array
    {
        $menu = NavigationMenu::query()->firstOrCreate(['audience' => $audience]);
        $entries = $this->catalog->autopublishableFor($audience);

        /** @var array<string, NavigationGroup> $groups */
        $groups = [];
        $created = 0;
        $updated = 0;

        foreach ($entries as $entry) {
            $group = $this->resolveGroup($menu, $entry, $groups);
            $item = $this->findItem($menu, $entry['route_name']);

            if ($item === null) {
                $this->createItem($group, $entry);
                $created++;

                continue;
            }

            if ($item->source === NavigationSource::Root || $item->overridden) {
                continue;
            }

            if ($this->applyCatalogAttributes($item, $group, $entry)) {
                $updated++;
            }
        }

        $hidden = $this->hideOrphans($menu);

        return ['created' => $created, 'updated' => $updated, 'hidden' => $hidden];
    }

    /**
     * Manually place a tagged-but-unplaced route for root, as a catalog item so
     * the reconciler keeps managing it. Returns the created item, or null when
     * the route is not eligible or already placed.
     */
    public function place(NavigationMenu $menu, string $routeName): ?NavigationItem
    {
        if (! $this->catalog->taggedFor($menu->audience, $routeName)) {
            return null;
        }

        if ($this->findItem($menu, $routeName) !== null) {
            return null;
        }

        $entry = $this->catalog->entryFor($menu->audience, $routeName);

        if ($entry === null) {
            return null;
        }

        /** @var array<string, NavigationGroup> $groups */
        $groups = [];
        $group = $this->resolveGroup($menu, $entry, $groups);
        $item = $this->createItem($group, $entry);

        NavigationCache::flush();

        return $item;
    }

    /**
     * Clear a root override on an item and return it to the catalog defaults.
     */
    public function restoreItem(NavigationItem $item): void
    {
        $item->loadMissing('group.menu');
        $routeName = (string) $item->route_name;
        $entry = $this->catalog->entryFor($item->group->menu->audience, $routeName);

        $attributes = ['overridden' => false, 'enabled' => true];

        if ($entry !== null) {
            /** @var array<string, NavigationGroup> $groups */
            $groups = [];
            $group = $this->resolveGroup($item->group->menu, $entry, $groups);

            $attributes['navigation_group_id'] = $group->id;
            $attributes['icon'] = $entry['icon'];
            $attributes['position'] = $this->itemPosition($entry);
        }

        $item->update($attributes);
        NavigationCache::flush();
    }

    /**
     * Clear a root override on a group and return it to the catalog defaults.
     */
    public function restoreGroup(NavigationGroup $group): void
    {
        $group->loadMissing('menu');
        $attributes = ['overridden' => false, 'enabled' => true];

        $entry = $this->entryForGroup($group->menu->audience, $group->label_key);

        if ($entry !== null && $entry['group_order'] !== null) {
            $attributes['position'] = $entry['group_order'];
        }

        $group->update($attributes);
        NavigationCache::flush();
    }

    /**
     * Tagged routes for the audience that have no placement yet.
     *
     * @return array<int, array{route_name: string, label_key: ?string, icon: ?string, group_key: ?string, group_icon: ?string, roles: array<int, string>|null, auto: bool, order: ?int, group_order: ?int, registration: int}>
     */
    public function availableFor(NavigationMenu $menu): array
    {
        $placed = NavigationItem::query()
            ->whereHas('group', fn ($query) => $query->where('navigation_menu_id', $menu->id))
            ->whereNotNull('route_name')
            ->pluck('route_name')
            ->all();

        return array_values(array_filter(
            $this->catalog->entriesFor($menu->audience),
            fn (array $entry): bool => ! in_array($entry['route_name'], $placed, true),
        ));
    }

    /**
     * @param  array{route_name: string, label_key: ?string, icon: ?string, group_key: ?string, group_icon: ?string, roles: array<int, string>|null, auto: bool, order: ?int, group_order: ?int, registration: int}  $entry
     * @param  array<string, NavigationGroup>  $groups
     */
    private function resolveGroup(NavigationMenu $menu, array $entry, array &$groups): NavigationGroup
    {
        $key = $entry['group_key'] ?? '__none__';

        if (isset($groups[$key])) {
            return $groups[$key];
        }

        $query = $menu->groups()->where('source', NavigationSource::Catalog);

        $group = $entry['group_key'] === null
            ? (clone $query)->whereNull('label_key')->first()
            : (clone $query)->where('label_key', $entry['group_key'])->first();

        if ($group === null) {
            $group = $menu->groups()->create([
                'label_key' => $entry['group_key'],
                'label_es' => null,
                'label_en' => null,
                'icon' => $entry['group_icon'],
                'position' => $this->groupPosition($menu, $entry, $groups),
                'enabled' => true,
                'source' => NavigationSource::Catalog,
                'overridden' => false,
            ]);
        } elseif (! $group->overridden) {
            $attributes = [];

            if ($entry['group_icon'] !== null && $group->icon !== $entry['group_icon']) {
                $attributes['icon'] = $entry['group_icon'];
            }

            if ($entry['group_order'] !== null && $group->position !== $entry['group_order']) {
                $attributes['position'] = $entry['group_order'];
            }

            if (! $group->enabled) {
                $attributes['enabled'] = true;
            }

            if ($attributes !== []) {
                $group->update($attributes);
            }
        }

        $groups[$key] = $group;

        return $group;
    }

    /**
     * @param  array{order: ?int, group_order: ?int, registration: int}  $entry
     * @param  array<string, NavigationGroup>  $groups
     */
    private function groupPosition(NavigationMenu $menu, array $entry, array $groups): int
    {
        if ($entry['group_order'] !== null) {
            return $entry['group_order'];
        }

        return ((int) $menu->groups()->max('position')) + 1 + count($groups);
    }

    /**
     * @param  array{route_name: string, icon: ?string, order: ?int, registration: int}  $entry
     */
    private function createItem(NavigationGroup $group, array $entry): NavigationItem
    {
        return $group->items()->create([
            'type' => NavigationItemType::Route,
            'route_name' => $entry['route_name'],
            'url' => null,
            'label_key' => null,
            'icon' => $entry['icon'],
            'position' => $this->itemPosition($entry),
            'enabled' => true,
            'source' => NavigationSource::Catalog,
            'overridden' => false,
        ]);
    }

    /**
     * @param  array{route_name: string, icon: ?string, order: ?int, registration: int}  $entry
     */
    private function applyCatalogAttributes(NavigationItem $item, NavigationGroup $group, array $entry): bool
    {
        $position = $this->itemPosition($entry);

        $stale = $item->navigation_group_id !== $group->id
            || $item->icon !== $entry['icon']
            || $item->position !== $position
            || ! $item->enabled;

        if (! $stale) {
            return false;
        }

        $item->update([
            'navigation_group_id' => $group->id,
            'icon' => $entry['icon'],
            'position' => $position,
            'enabled' => true,
        ]);

        return true;
    }

    /**
     * Hide (never delete) catalog route items whose route is gone or no longer
     * tagged for the audience. Root-authored items are left untouched.
     */
    private function hideOrphans(NavigationMenu $menu): int
    {
        $hidden = 0;

        $items = NavigationItem::query()
            ->where('source', NavigationSource::Catalog)
            ->where('type', NavigationItemType::Route)
            ->whereHas('group', fn ($query) => $query->where('navigation_menu_id', $menu->id))
            ->get();

        foreach ($items as $item) {
            if ($this->catalog->taggedFor($menu->audience, $item->route_name)) {
                continue;
            }

            if ($item->enabled) {
                $item->update(['enabled' => false]);
                $hidden++;
            }
        }

        return $hidden;
    }

    private function findItem(NavigationMenu $menu, string $routeName): ?NavigationItem
    {
        return NavigationItem::query()
            ->where('route_name', $routeName)
            ->whereHas('group', fn ($query) => $query->where('navigation_menu_id', $menu->id))
            ->first();
    }

    /**
     * @return array{group_order: ?int}|null
     */
    private function entryForGroup(NavigationAudience $audience, ?string $labelKey): ?array
    {
        foreach ($this->catalog->entriesFor($audience) as $entry) {
            if ($entry['group_key'] === $labelKey) {
                return ['group_order' => $entry['group_order']];
            }
        }

        return null;
    }

    /**
     * @param  array{order: ?int, registration: int}  $entry
     */
    private function itemPosition(array $entry): int
    {
        return $entry['order'] ?? $entry['registration'];
    }
}
