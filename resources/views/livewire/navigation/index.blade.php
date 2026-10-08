@php
    $audienceLabels = [
        'root' => __('Root'),
        'admin' => __('Admin'),
        'user' => __('Users without role'),
    ];
@endphp

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="space-y-1">
            <flux:heading level="1">{{ __('Navigation') }}</flux:heading>
            <flux:text>{{ __('Configure the sidebar for each audience. Package routes are offered by their labels; root decides what is shown and in what order.') }}</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openGroupCreator">
            {{ __('Add group') }}
        </flux:button>
    </div>

    <div class="max-w-xs">
        <flux:select wire:model.live="audience" :label="__('Audience')">
            @foreach ($audiences as $case)
                <flux:select.option :value="$case->value">{{ $audienceLabels[$case->value] ?? $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div wire:sort="handleGroupSort" class="space-y-4">
        @forelse ($groups as $group)
            <flux:card wire:key="group-{{ $group->id }}" wire:sort:item="{{ $group->id }}">
                <div class="flex flex-wrap items-center gap-3">
                    <flux:button size="sm" variant="ghost" icon="bars-3" wire:sort:handle :aria-label="__('Reorder group')" />

                    <flux:icon :name="$group->icon ?? 'folder'" variant="mini" class="size-4 text-zinc-400" />

                    <div class="min-w-0 flex-1">
                        <flux:heading>{{ $group->label() ?? __('No heading') }}</flux:heading>
                        @if ($group->label_key !== null)
                            <flux:text class="text-xs">{{ __('Translation key: :key', ['key' => $group->label_key]) }}</flux:text>
                        @endif
                    </div>

                    @unless ($group->enabled)
                        <flux:badge color="amber">{{ __('Hidden') }}</flux:badge>
                    @endunless

                    <flux:button
                        size="sm"
                        variant="ghost"
                        :icon="$group->enabled ? 'eye' : 'eye-slash'"
                        wire:click="toggleGroup({{ $group->id }})"
                    >
                        {{ $group->enabled ? __('Hide') : __('Show') }}
                    </flux:button>

                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openGroupEditor({{ $group->id }})">
                        {{ __('Edit') }}
                    </flux:button>

                    <flux:button
                        size="sm"
                        variant="ghost"
                        icon="trash"
                        wire:click="deleteGroup({{ $group->id }})"
                        wire:confirm="{{ __('Delete this group and all of its items?') }}"
                    >
                        {{ __('Delete') }}
                    </flux:button>
                </div>

                <div
                    wire:sort="handleItemSort"
                    wire:sort:group="navigation-items"
                    wire:sort:group-id="{{ $group->id }}"
                    class="mt-4 space-y-2"
                >
                    @foreach ($group->items as $item)
                        <div
                            wire:key="item-{{ $item->id }}"
                            wire:sort:item="{{ $item->id }}"
                            class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700"
                        >
                            <flux:button size="sm" variant="ghost" icon="bars-3" wire:sort:handle :aria-label="__('Reorder item')" />

                            <flux:icon :name="$item->menuIcon() ?? ($item->type === \App\Enums\NavigationItemType::Route ? 'link' : 'arrow-top-right-on-square')" variant="mini" class="size-4 text-zinc-400" />

                            <div class="min-w-0 flex-1">
                                <flux:text class="truncate font-medium">{{ $item->menuLabel() }}</flux:text>
                                <flux:text class="truncate text-xs text-zinc-500">{{ $item->destination() }}</flux:text>
                            </div>

                            <flux:badge color="zinc">{{ $item->type === \App\Enums\NavigationItemType::Route ? __('Route') : __('Link') }}</flux:badge>

                            @unless ($item->enabled)
                                <flux:badge color="amber">{{ __('Hidden') }}</flux:badge>
                            @endunless

                            <flux:button
                                size="sm"
                                variant="ghost"
                                :icon="$item->enabled ? 'eye' : 'eye-slash'"
                                wire:click="toggleItem({{ $item->id }})"
                            >
                                {{ $item->enabled ? __('Hide') : __('Show') }}
                            </flux:button>

                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openItemEditor({{ $item->id }})">
                                {{ __('Edit') }}
                            </flux:button>

                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                wire:click="deleteItem({{ $item->id }})"
                                wire:confirm="{{ __('Delete this item?') }}"
                            >
                                {{ __('Delete') }}
                            </flux:button>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    <flux:button size="sm" variant="ghost" icon="plus" wire:click="openItemCreator({{ $group->id }})">
                        {{ __('Add item') }}
                    </flux:button>
                </div>
            </flux:card>
        @empty
            <flux:card>
                <flux:text>{{ __('This menu has no groups yet.') }}</flux:text>
            </flux:card>
        @endforelse
    </div>

    <flux:modal name="navigation-group" wire:model.self="showGroupModal" @close="closeGroupModal" class="max-w-md space-y-6">
        <flux:heading size="lg">{{ $editingGroupId === null ? __('Add group') : __('Edit group') }}</flux:heading>

        <flux:input wire:model="groupLabelEs" :label="__('Label (Spanish)')" />
        <flux:input wire:model="groupLabelEn" :label="__('Label (English)')" />
        <flux:input wire:model="groupIcon" :label="__('Icon')" :placeholder="__('Heroicon name, e.g. cog-6-tooth')" />

        <div class="flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="closeGroupModal">{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" wire:click="saveGroup">{{ __('Save') }}</flux:button>
        </div>
    </flux:modal>

    <flux:modal name="navigation-item" wire:model.self="showItemModal" @close="closeItemModal" class="max-w-lg space-y-6">
        <flux:heading size="lg">{{ $editingItemId === null ? __('Add item') : __('Edit item') }}</flux:heading>

        <flux:radio.group wire:model.live="itemType" :label="__('Type')">
            <flux:radio value="route" :label="__('Internal route')" />
            <flux:radio value="link" :label="__('External link or download')" />
        </flux:radio.group>

        @if ($itemType === 'route')
            <flux:select wire:model.live="routePicker" :label="__('Available routes')">
                <flux:select.option value="">{{ __('— Choose a route —') }}</flux:select.option>
                @foreach ($routeOptions as $option)
                    <flux:select.option :value="$option['value']">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="itemRoute" :label="__('Route name')" :placeholder="__('Or type a route name (advanced)')" />

            @if (blank($itemRoute) || \Illuminate\Support\Facades\Route::has($itemRoute) === false || ! is_array(\Illuminate\Support\Facades\Route::getRoutes()->getByName($itemRoute)?->defaults['menu'] ?? null))
                <flux:input wire:model="itemLabelEs" :label="__('Label (Spanish)')" />
                <flux:input wire:model="itemLabelEn" :label="__('Label (English)')" />
            @endif
        @else
            <flux:input wire:model="itemUrl" :label="__('Destination')" :placeholder="__('https://example.com or /internal/path')" />
            <flux:input wire:model="itemLabelEs" :label="__('Label (Spanish)')" />
            <flux:input wire:model="itemLabelEn" :label="__('Label (English)')" />
        @endif

        <flux:input wire:model="itemIcon" :label="__('Icon')" :placeholder="__('Heroicon name (optional)')" />

        <div class="flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="closeItemModal">{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" wire:click="saveItem">{{ __('Save') }}</flux:button>
        </div>
    </flux:modal>
</section>
