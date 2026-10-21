<?php

namespace Database\Seeders;

use App\Actions\NavigationSync;
use App\Enums\NavigationAudience;
use App\Enums\NavigationItemType;
use App\Enums\NavigationRegion;
use App\Enums\NavigationSource;
use App\Models\NavigationMenu;
use Illuminate\Database\Seeder;

/**
 * Materializes the default sidebar into the database so a fresh install works
 * out of the box and root can then reorder or hide items.
 *
 * The sidebar is split in two regions: `main` holds the app's own features
 * (Dashboard; the app's routes as they are tagged) and `bottom` is the curated
 * set painted over the user menu, per role. The `main` defaults are only built
 * when the menu is created, so re-running never overwrites root's
 * configuration; the `bottom` region is ensured idempotently so upgrades from
 * the pre-region layout also get the curated set (retiring the untouched
 * legacy catalog groups that now live inside Developer Mode / Administration).
 * Any route tagged for autopublishing is then reconciled in `main`.
 */
class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (NavigationAudience::cases() as $audience) {
            $menu = NavigationMenu::query()->firstOrCreate(['audience' => $audience]);

            if ($menu->wasRecentlyCreated) {
                $this->buildGroups($menu, $this->mainGroups());
            }

            $this->ensureBottom($menu, $audience);
        }

        app(NavigationSync::class)->handle();
    }

    /**
     * Create the curated `bottom` region for the audience when it is missing.
     * Untouched legacy catalog groups that are now represented in the curated
     * set are hidden so an upgraded install does not show them twice.
     */
    private function ensureBottom(NavigationMenu $menu, NavigationAudience $audience): void
    {
        if ($menu->groups()->where('region', NavigationRegion::Bottom)->exists()) {
            return;
        }

        $this->retireLegacyGroups($menu, $this->legacyGroupKeys($audience));

        $groups = $this->bottomGroups($audience);

        if ($groups === []) {
            return;
        }

        $this->buildGroups($menu, $groups, NavigationRegion::Bottom, NavigationSource::Root);
    }

    /**
     * Hide legacy catalog groups (untouched by root) from `main`. Marking them
     * overridden keeps reconciliation from re-enabling them; root can still
     * show them again from the navigation manager.
     *
     * @param  array<int, string>  $labelKeys
     */
    private function retireLegacyGroups(NavigationMenu $menu, array $labelKeys): void
    {
        if ($labelKeys === []) {
            return;
        }

        $menu->groups()
            ->where('region', NavigationRegion::Main)
            ->where('source', NavigationSource::Catalog)
            ->where('overridden', false)
            ->whereIn('label_key', $labelKeys)
            ->update(['enabled' => false, 'overridden' => true]);
    }

    /**
     * @param  array<int, array{label_key: ?string, icon: ?string, items: array<int, array<string, mixed>>}>  $groups
     */
    private function buildGroups(NavigationMenu $menu, array $groups, NavigationRegion $region = NavigationRegion::Main, NavigationSource $source = NavigationSource::Catalog): void
    {
        foreach (array_values($groups) as $groupPosition => $group) {
            $created = $menu->groups()->create([
                'region' => $region,
                'label_key' => $group['label_key'],
                'icon' => $group['icon'],
                'position' => $groupPosition,
                'enabled' => true,
                'source' => $source,
                'overridden' => false,
            ]);

            foreach (array_values($group['items']) as $itemPosition => $item) {
                $type = $item['type'] ?? NavigationItemType::Route;

                $created->items()->create([
                    'type' => $type,
                    'route_name' => $item['route_name'] ?? null,
                    'url' => $item['url'] ?? null,
                    'label_key' => $item['label_key'] ?? null,
                    'label_es' => $item['label_es'] ?? null,
                    'label_en' => $item['label_en'] ?? null,
                    'icon' => $item['icon'] ?? null,
                    'position' => $itemPosition,
                    'enabled' => true,
                    'source' => $type === NavigationItemType::Link ? NavigationSource::Root : $source,
                    'overridden' => false,
                ]);
            }
        }
    }

    /**
     * The `main` region is the app's own surface: only Dashboard by default.
     * App routes reach it through their tags and reconciliation.
     *
     * @return array<int, array{label_key: ?string, icon: ?string, items: array<int, array<string, mixed>>}>
     */
    private function mainGroups(): array
    {
        return [
            ['label_key' => null, 'icon' => null, 'items' => [
                ['route_name' => 'dashboard'],
            ]],
        ];
    }

    /**
     * @return array<int, array{label_key: ?string, icon: ?string, items: array<int, array<string, mixed>>}>
     */
    private function bottomGroups(NavigationAudience $audience): array
    {
        return match ($audience) {
            NavigationAudience::Root => [
                ['label_key' => 'Developer Mode', 'icon' => 'code-bracket', 'items' => [
                    ['route_name' => 'support.index'],
                    ['route_name' => 'admin.users.index'],
                    ['route_name' => 'admin.activity.index'],
                    ['route_name' => 'admin.settings.edit'],
                    ['route_name' => 'admin.setup.index'],
                    ['route_name' => 'admin.ai.index'],
                    ['route_name' => 'admin.navigation.index'],
                    ['route_name' => 'admin.help.index'],
                    ['route_name' => 'admin.packages.index'],
                    ['route_name' => 'admin.packages.lnx-ui'],
                    ['route_name' => 'help.index'],
                    ['route_name' => 'chat'],
                    ['route_name' => 'knowledge.index'],
                    ['route_name' => 'documents.index'],
                    ['route_name' => 'admin.ai.usage', 'label_key' => 'AI usage', 'icon' => 'chart-bar'],
                ]],
            ],
            NavigationAudience::Admin => [
                ['label_key' => 'Administration', 'icon' => 'cog-6-tooth', 'items' => [
                    ['route_name' => 'admin.users.index'],
                    ['route_name' => 'help.index'],
                ]],
            ],
            NavigationAudience::User => [
                ['label_key' => 'Help center', 'icon' => 'lifebuoy', 'items' => [
                    ['route_name' => 'help.index'],
                ]],
            ],
        };
    }

    /**
     * Legacy catalog groups from the pre-region layout that now live inside the
     * curated bottom. Used only to retire untouched defaults on upgrade.
     *
     * @return array<int, string>
     */
    private function legacyGroupKeys(NavigationAudience $audience): array
    {
        return match ($audience) {
            NavigationAudience::Root => ['Administration', 'Packages', 'AI Tools'],
            NavigationAudience::Admin => ['Administration', 'AI Tools'],
            NavigationAudience::User => ['AI Tools'],
        };
    }
}
