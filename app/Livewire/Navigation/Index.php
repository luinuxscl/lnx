<?php

namespace App\Livewire\Navigation;

use App\Actions\NavigationSync;
use App\Actions\RecordAdministrativeActivity;
use App\Enums\AdministrativeAction;
use App\Enums\NavigationAudience;
use App\Enums\NavigationItemType;
use App\Enums\NavigationSource;
use App\Enums\Role;
use App\Models\NavigationGroup;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Rules\SafeNavigationUrl;
use App\Support\Navigation\NavigationCache;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Navigation')]
class Index extends Component
{
    #[Url(as: 'audience')]
    public string $audience = 'root';

    public bool $showGroupModal = false;

    public ?int $editingGroupId = null;

    public string $groupLabelEs = '';

    public string $groupLabelEn = '';

    public string $groupIcon = '';

    public bool $showItemModal = false;

    public ?int $itemGroupId = null;

    public ?int $editingItemId = null;

    public string $itemType = 'route';

    public string $itemRoute = '';

    public string $routePicker = '';

    public string $itemUrl = '';

    public string $itemLabelEs = '';

    public string $itemLabelEn = '';

    public string $itemIcon = '';

    public function mount(): void
    {
        $this->authorizeRoot();
        $this->normalizeAudience();
    }

    public function updatedAudience(): void
    {
        $this->normalizeAudience();
        $this->closeGroupModal();
        $this->closeItemModal();
    }

    public function updatedRoutePicker(string $value): void
    {
        if ($value !== '') {
            $this->itemRoute = $value;
        }
    }

    public function updatedItemType(): void
    {
        $this->resetValidation();
    }

    // ----- Groups -----

    public function openGroupCreator(): void
    {
        $this->authorizeRoot();
        $this->editingGroupId = null;
        $this->groupLabelEs = '';
        $this->groupLabelEn = '';
        $this->groupIcon = '';
        $this->resetValidation();
        $this->showGroupModal = true;
    }

    public function openGroupEditor(int $groupId): void
    {
        $this->authorizeRoot();
        $group = $this->groupOrFail($groupId);

        $this->editingGroupId = $group->id;
        [$this->groupLabelEs, $this->groupLabelEn] = $this->authoredLabels($group);
        $this->groupIcon = (string) $group->icon;
        $this->resetValidation();
        $this->showGroupModal = true;
    }

    public function saveGroup(): void
    {
        $this->authorizeRoot();

        $data = $this->validate([
            'groupLabelEs' => ['required', 'string', 'max:255'],
            'groupLabelEn' => ['required', 'string', 'max:255'],
            'groupIcon' => ['nullable', 'string', 'max:255'],
        ]);

        $attributes = [
            'label_key' => null,
            'label_es' => $data['groupLabelEs'],
            'label_en' => $data['groupLabelEn'],
            'icon' => $data['groupIcon'] !== '' ? $data['groupIcon'] : null,
        ];

        if ($this->editingGroupId !== null) {
            $group = $this->groupOrFail($this->editingGroupId);
            $before = $group->only(array_keys($attributes));
            $group->update($this->markOverridden($group, $attributes));
            $this->record(AdministrativeAction::NavigationGroupUpdated, $group, $before, $group->only(array_keys($attributes)));
            Flux::toast(variant: 'success', text: __('Group updated.'));
        } else {
            $group = $this->menu()->groups()->create($attributes + [
                'position' => ((int) $this->menu()->groups()->max('position')) + 1,
                'enabled' => true,
            ]);
            $this->record(AdministrativeAction::NavigationGroupCreated, $group, [], $this->groupSnapshot($group));
            Flux::toast(variant: 'success', text: __('Group created.'));
        }

        $this->closeGroupModal();
    }

    public function toggleGroup(int $groupId): void
    {
        $this->authorizeRoot();
        $group = $this->groupOrFail($groupId);
        $before = ['enabled' => $group->enabled];
        $group->update($this->markOverridden($group, ['enabled' => ! $group->enabled]));
        $this->record(AdministrativeAction::NavigationGroupUpdated, $group, $before, ['enabled' => $group->enabled]);
    }

    public function deleteGroup(int $groupId): void
    {
        $this->authorizeRoot();
        $group = $this->groupOrFail($groupId);
        $this->record(AdministrativeAction::NavigationGroupDeleted, $group, $this->groupSnapshot($group));

        if ($group->source === NavigationSource::Catalog) {
            $group->update(['overridden' => true, 'enabled' => false]);
        } else {
            $group->delete();
        }

        Flux::toast(variant: 'success', text: __('Group deleted.'));
    }

    public function restoreGroup(int $groupId): void
    {
        $this->authorizeRoot();
        $group = $this->groupOrFail($groupId);

        app(NavigationSync::class)->restoreGroup($group);

        $this->record(AdministrativeAction::NavigationGroupUpdated, $group, [], ['overridden' => false, 'enabled' => true]);
        Flux::toast(variant: 'success', text: __('Group restored to defaults.'));
    }

    public function handleGroupSort(int $id, int $position): void
    {
        $this->authorizeRoot();
        $group = $this->groupOrFail($id);
        $before = ['position' => $group->position];

        $ids = $this->menu()->groups()->pluck('id')->all();
        $this->persistOrder($ids, $id, $position, NavigationGroup::class);

        $group->refresh();
        $this->record(AdministrativeAction::NavigationGroupUpdated, $group, $before, ['position' => $group->position]);

        Flux::toast(variant: 'success', text: __('Order updated.'));
    }

    public function closeGroupModal(): void
    {
        $this->showGroupModal = false;
        $this->editingGroupId = null;
    }

    // ----- Items -----

    public function openItemCreator(int $groupId): void
    {
        $this->authorizeRoot();
        $this->groupOrFail($groupId);

        $this->itemGroupId = $groupId;
        $this->editingItemId = null;
        $this->itemType = NavigationItemType::Route->value;
        $this->itemRoute = '';
        $this->routePicker = '';
        $this->itemUrl = '';
        $this->itemLabelEs = '';
        $this->itemLabelEn = '';
        $this->itemIcon = '';
        $this->resetValidation();
        $this->showItemModal = true;
    }

    public function openItemEditor(int $itemId): void
    {
        $this->authorizeRoot();
        $item = $this->itemOrFail($itemId);

        $this->itemGroupId = $item->navigation_group_id;
        $this->editingItemId = $item->id;
        $this->itemType = $item->type->value;
        $this->itemRoute = (string) $item->route_name;
        $this->routePicker = '';
        $this->itemUrl = (string) $item->url;
        $this->itemLabelEs = (string) $item->label_es;
        $this->itemLabelEn = (string) $item->label_en;
        $this->itemIcon = (string) $item->icon;
        $this->resetValidation();
        $this->showItemModal = true;
    }

    public function saveItem(): void
    {
        $this->authorizeRoot();

        if ($this->itemType === NavigationItemType::Link->value) {
            $attributes = $this->validateLinkAttributes();
        } else {
            $attributes = $this->validateRouteAttributes();

            if ($attributes === null) {
                return;
            }
        }

        if ($this->editingItemId !== null) {
            $item = $this->itemOrFail($this->editingItemId);
            $before = $item->only(array_keys($attributes));
            $item->update($this->markOverridden($item, $attributes));
            $this->record(AdministrativeAction::NavigationItemUpdated, $item, $before, $item->only(array_keys($attributes)));
            Flux::toast(variant: 'success', text: __('Item updated.'));
        } else {
            $group = $this->groupOrFail((int) $this->itemGroupId);
            $item = $group->items()->create($attributes + [
                'position' => ((int) $group->items()->max('position')) + 1,
                'enabled' => true,
            ]);
            $this->record(AdministrativeAction::NavigationItemCreated, $item, [], $this->itemSnapshot($item));
            Flux::toast(variant: 'success', text: __('Item created.'));
        }

        $this->closeItemModal();
    }

    public function toggleItem(int $itemId): void
    {
        $this->authorizeRoot();
        $item = $this->itemOrFail($itemId);
        $before = ['enabled' => $item->enabled];
        $item->update($this->markOverridden($item, ['enabled' => ! $item->enabled]));
        $this->record(AdministrativeAction::NavigationItemUpdated, $item, $before, ['enabled' => $item->enabled]);
    }

    public function deleteItem(int $itemId): void
    {
        $this->authorizeRoot();
        $item = $this->itemOrFail($itemId);
        $this->record(AdministrativeAction::NavigationItemDeleted, $item, $this->itemSnapshot($item));

        if ($item->source === NavigationSource::Catalog) {
            $item->update(['overridden' => true, 'enabled' => false]);
        } else {
            $item->delete();
        }

        Flux::toast(variant: 'success', text: __('Item deleted.'));
    }

    public function restoreItem(int $itemId): void
    {
        $this->authorizeRoot();
        $item = $this->itemOrFail($itemId);

        app(NavigationSync::class)->restoreItem($item);

        $item->refresh();
        $this->record(AdministrativeAction::NavigationItemUpdated, $item, [], $this->itemSnapshot($item));
        Flux::toast(variant: 'success', text: __('Item restored to defaults.'));
    }

    public function placeRoute(string $routeName): void
    {
        $this->authorizeRoot();

        $item = app(NavigationSync::class)->place($this->menu(), $routeName);

        if ($item !== null) {
            $this->record(AdministrativeAction::NavigationItemCreated, $item, [], $this->itemSnapshot($item));
            Flux::toast(variant: 'success', text: __('Route added to the menu.'));
        }
    }

    public function handleItemSort(int $id, int $position, int $groupId): void
    {
        $this->authorizeRoot();
        $item = $this->itemOrFail($id);
        $group = $this->groupOrFail($groupId);
        $before = ['position' => $item->position];

        if ($item->navigation_group_id !== $group->id) {
            $item->update(['navigation_group_id' => $group->id]);
        }

        $ids = $group->items()->pluck('id')->all();
        $this->persistOrder($ids, $id, $position, NavigationItem::class);

        $item->refresh();
        $this->record(AdministrativeAction::NavigationItemUpdated, $item, $before, ['position' => $item->position]);

        Flux::toast(variant: 'success', text: __('Order updated.'));
    }

    public function closeItemModal(): void
    {
        $this->showItemModal = false;
        $this->itemGroupId = null;
        $this->editingItemId = null;
    }

    public function render(): View
    {
        $this->authorizeRoot();

        $menu = $this->menu();

        return view('livewire.navigation.index', [
            'groups' => $menu->groups()->with('items')->get(),
            'audiences' => NavigationAudience::cases(),
            'routeOptions' => $this->routeOptions(),
            'available' => app(NavigationSync::class)->availableFor($menu),
        ])->layout('layouts.app', [
            'breadcrumbs' => [
                ['label' => __('Administration')],
                ['label' => __('Navigation')],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateLinkAttributes(): array
    {
        $data = $this->validate([
            'itemUrl' => ['required', 'string', new SafeNavigationUrl],
            'itemLabelEs' => ['required', 'string', 'max:255'],
            'itemLabelEn' => ['required', 'string', 'max:255'],
            'itemIcon' => ['nullable', 'string', 'max:255'],
        ]);

        return [
            'type' => NavigationItemType::Link,
            'route_name' => null,
            'url' => trim($data['itemUrl']),
            'label_key' => null,
            'label_es' => $data['itemLabelEs'],
            'label_en' => $data['itemLabelEn'],
            'icon' => $data['itemIcon'] !== '' ? $data['itemIcon'] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validateRouteAttributes(): ?array
    {
        $this->validate([
            'itemRoute' => ['required', 'string', 'max:255'],
            'itemIcon' => ['nullable', 'string', 'max:255'],
        ]);

        if (! Route::has($this->itemRoute)) {
            $this->addError('itemRoute', __('The selected route does not exist.'));

            return null;
        }

        $untagged = $this->routeTag($this->itemRoute) === null;

        if ($untagged) {
            $this->validate([
                'itemLabelEs' => ['required', 'string', 'max:255'],
                'itemLabelEn' => ['required', 'string', 'max:255'],
            ]);
        }

        return [
            'type' => NavigationItemType::Route,
            'route_name' => $this->itemRoute,
            'url' => null,
            'label_key' => null,
            'label_es' => $untagged ? $this->itemLabelEs : null,
            'label_en' => $untagged ? $this->itemLabelEn : null,
            'icon' => $this->itemIcon !== '' ? $this->itemIcon : null,
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function routeOptions(): array
    {
        $options = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $tag = $route->defaults['menu'] ?? null;

            if (! is_array($tag) || $route->getName() === null) {
                continue;
            }

            $options[] = [
                'value' => $route->getName(),
                'label' => __($tag['label'] ?? $route->getName()).' · '.$route->getName(),
            ];
        }

        return $options;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function routeTag(string $routeName): ?array
    {
        $route = Route::getRoutes()->getByName($routeName);
        $tag = $route?->defaults['menu'] ?? null;

        return is_array($tag) ? $tag : null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function authoredLabels(NavigationGroup $group): array
    {
        if ($group->label_key !== null) {
            return [
                (string) trans($group->label_key, [], 'es'),
                (string) trans($group->label_key, [], 'en'),
            ];
        }

        return [(string) $group->label_es, (string) $group->label_en];
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function persistOrder(array $ids, int $id, int $position, string $model): void
    {
        $ids = array_values(array_filter($ids, fn (int $value): bool => $value !== $id));
        $position = max(0, min($position, count($ids)));
        array_splice($ids, $position, 0, [$id]);

        foreach ($ids as $index => $orderId) {
            $model::query()->whereKey($orderId)->update(['position' => $index]);
        }

        $model::query()
            ->whereIn('id', $ids)
            ->where('source', NavigationSource::Catalog)
            ->update(['overridden' => true]);

        NavigationCache::flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function groupSnapshot(NavigationGroup $group): array
    {
        return $group->only(['audience', 'label_key', 'label_es', 'label_en', 'icon', 'position', 'enabled']);
    }

    /**
     * @return array<string, mixed>
     */
    private function itemSnapshot(NavigationItem $item): array
    {
        return $item->only(['audience', 'type', 'route_name', 'url', 'label_key', 'label_es', 'label_en', 'icon', 'position', 'enabled']);
    }

    /**
     * Mark a catalog-authored model as overridden so the reconciler never
     * overwrites root's decision. Root-authored models are returned untouched.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function markOverridden(NavigationItem|NavigationGroup $model, array $attributes): array
    {
        if ($model->source === NavigationSource::Catalog) {
            $attributes['overridden'] = true;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    private function record(AdministrativeAction $action, Model $resource, array $before = [], array $after = []): void
    {
        app(RecordAdministrativeActivity::class)->handle(auth()->user(), $action, $resource, $before, $after);
    }

    private function menu(): NavigationMenu
    {
        return NavigationMenu::query()->firstOrCreate(['audience' => NavigationAudience::from($this->audience)]);
    }

    private function groupOrFail(int $id): NavigationGroup
    {
        return $this->menu()->groups()->whereKey($id)->firstOrFail();
    }

    private function itemOrFail(int $id): NavigationItem
    {
        return NavigationItem::query()
            ->whereKey($id)
            ->whereHas('group', fn ($query) => $query->where('navigation_menu_id', $this->menu()->id))
            ->firstOrFail();
    }

    private function normalizeAudience(): void
    {
        if (NavigationAudience::tryFrom($this->audience) === null) {
            $this->audience = NavigationAudience::Root->value;
        }
    }

    private function authorizeRoot(): void
    {
        abort_unless(auth()->user()?->hasRole(Role::Root), 403);
    }
}
