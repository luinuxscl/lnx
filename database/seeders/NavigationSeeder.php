<?php

namespace Database\Seeders;

use App\Actions\NavigationSync;
use App\Enums\NavigationAudience;
use App\Enums\NavigationItemType;
use App\Enums\NavigationSource;
use App\Models\NavigationMenu;
use Illuminate\Database\Seeder;

/**
 * Materializes the default sidebar into the database so a fresh install works
 * out of the box and root can then reorder or hide items. It only creates the
 * menu when none exists for the audience, so re-running it never overwrites a
 * root's configuration. Route items are marked as catalog; links root-authored.
 * Any route tagged for autopublishing is then reconciled by NavigationSync.
 */
class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        $this->buildMenu(NavigationAudience::Root, $this->rootGroups());
        $this->buildMenu(NavigationAudience::Admin, $this->adminGroups());
        $this->buildMenu(NavigationAudience::User, $this->userGroups());

        app(NavigationSync::class)->handle();
    }

    /**
     * @param  array<int, array{label_key: ?string, icon: ?string, items: array<int, array<string, mixed>>}>  $groups
     */
    private function buildMenu(NavigationAudience $audience, array $groups): void
    {
        $menu = NavigationMenu::query()->firstOrCreate(['audience' => $audience]);

        if (! $menu->wasRecentlyCreated) {
            return;
        }

        foreach (array_values($groups) as $groupPosition => $group) {
            $created = $menu->groups()->create([
                'label_key' => $group['label_key'],
                'icon' => $group['icon'],
                'position' => $groupPosition,
                'enabled' => true,
                'source' => NavigationSource::Catalog,
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
                    'source' => $type === NavigationItemType::Link ? NavigationSource::Root : NavigationSource::Catalog,
                    'overridden' => false,
                ]);
            }
        }
    }

    /**
     * @return array<int, array{label_key: ?string, icon: ?string, items: array<int, array<string, mixed>>}>
     */
    private function rootGroups(): array
    {
        return [
            ['label_key' => null, 'icon' => null, 'items' => [
                ['route_name' => 'dashboard'],
            ]],
            ['label_key' => 'Administration', 'icon' => 'cog-6-tooth', 'items' => [
                ['route_name' => 'support.index'],
                ['route_name' => 'admin.users.index'],
                ['route_name' => 'admin.activity.index'],
                ['route_name' => 'admin.settings.edit'],
                ['route_name' => 'admin.setup.index'],
                ['route_name' => 'admin.ai.index'],
                ['route_name' => 'admin.navigation.index'],
            ]],
            ['label_key' => 'Packages', 'icon' => 'cube', 'items' => [
                ['route_name' => 'admin.packages.index'],
                ['route_name' => 'admin.packages.lnx-ui'],
            ]],
            ['label_key' => 'AI Tools', 'icon' => 'sparkles', 'items' => [
                ['route_name' => 'chat'],
                ['route_name' => 'knowledge.index'],
                ['route_name' => 'documents.index'],
            ]],
        ];
    }

    /**
     * @return array<int, array{label_key: ?string, icon: ?string, items: array<int, array<string, mixed>>}>
     */
    private function adminGroups(): array
    {
        return [
            ['label_key' => null, 'icon' => null, 'items' => [
                ['route_name' => 'dashboard'],
            ]],
            ['label_key' => 'Administration', 'icon' => 'cog-6-tooth', 'items' => [
                ['route_name' => 'admin.users.index'],
                ['type' => NavigationItemType::Link, 'label_key' => 'Documentation'],
            ]],
            ['label_key' => 'AI Tools', 'icon' => 'sparkles', 'items' => [
                ['route_name' => 'chat'],
                ['route_name' => 'knowledge.index'],
                ['route_name' => 'documents.index'],
            ]],
        ];
    }

    /**
     * @return array<int, array{label_key: ?string, icon: ?string, items: array<int, array<string, mixed>>}>
     */
    private function userGroups(): array
    {
        return [
            ['label_key' => null, 'icon' => null, 'items' => [
                ['route_name' => 'dashboard'],
            ]],
            ['label_key' => 'AI Tools', 'icon' => 'sparkles', 'items' => [
                ['route_name' => 'chat'],
                ['route_name' => 'knowledge.index'],
                ['route_name' => 'documents.index'],
            ]],
        ];
    }
}
