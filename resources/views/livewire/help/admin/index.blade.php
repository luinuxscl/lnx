<section class="w-full space-y-6">
    <x-lnx-ui::page-header
        :title="__('Help center')"
        :subtitle="__('Packages propose the documentation; root decides what is published. Edits, order and visibility are preserved when help:sync runs.')"
        icon="book-open"
        color="cyan"
    >
        <x-slot:actions>
            <flux:button icon="arrow-top-right-on-square" :href="route('help.index')" wire:navigate>
                {{ __('Open help center') }}
            </flux:button>
        </x-slot:actions>
    </x-lnx-ui::page-header>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <flux:card class="space-y-1">
            <flux:text class="text-xs">{{ __('Auto') }}</flux:text>
            <flux:heading size="lg">{{ $counts['auto'] }}</flux:heading>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text class="text-xs">{{ __('Edited by root') }}</flux:text>
            <flux:heading size="lg">{{ $counts['overridden'] }}</flux:heading>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text class="text-xs">{{ __('Hidden') }}</flux:text>
            <flux:heading size="lg">{{ $counts['hidden'] }}</flux:heading>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text class="text-xs">{{ __('Available') }}</flux:text>
            <flux:heading size="lg">{{ $counts['available'] }}</flux:heading>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text class="text-xs">{{ __('Packages') }}</flux:text>
            <flux:heading size="lg">{{ count($sources) }}</flux:heading>
        </flux:card>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" :placeholder="__('Slug or title')" icon="magnifying-glass" clearable />
        <flux:select wire:model.live="source" :label="__('Package')">
            <flux:select.option value="">{{ __('All packages') }}</flux:select.option>
            @foreach ($sources as $option)
                <flux:select.option :value="$option">{{ $option }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="type" :label="__('Type')">
            <flux:select.option value="">{{ __('All types') }}</flux:select.option>
            <flux:select.option value="article">{{ __('Guide') }}</flux:select.option>
            <flux:select.option value="faq">{{ __('FAQ') }}</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="status" :label="__('Status')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="auto">{{ __('Auto') }}</flux:select.option>
            <flux:select.option value="overridden">{{ __('Edited by root') }}</flux:select.option>
            <flux:select.option value="available">{{ __('Available') }}</flux:select.option>
            <flux:select.option value="hidden">{{ __('Hidden') }}</flux:select.option>
        </flux:select>
    </div>

    @forelse ($sources as $sourceName)
        @php($sourceGroups = $groups->filter(fn ($sourceEntries, $key) => str_starts_with($key, $sourceName.'|')))
        @php($availableForSource = array_values(array_filter($available, fn (array $entry): bool => $entry['source'] === $sourceName)))

        @continue($sourceGroups->isEmpty() && $availableForSource === [])

        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading level="2">{{ $sourceName }}</flux:heading>
                <flux:badge color="zinc">{{ __('From :source', ['source' => $sourceName]) }}</flux:badge>
            </div>

            @foreach ($sourceGroups as $groupEntries)
                @php($group = $groupEntries->first()->group)

                <flux:card wire:key="help-group-{{ $group->id }}" class="space-y-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <flux:heading>{{ __($group->group_key) }}</flux:heading>
                            <flux:text class="text-xs">{{ $group->group_key }}</flux:text>
                        </div>

                        <flux:badge :color="$group->overridden ? 'amber' : 'blue'">
                            {{ $group->overridden ? __('Edited by root') : __('Auto') }}
                        </flux:badge>

                        @unless ($group->enabled)
                            <flux:badge color="amber">{{ __('Hidden') }}</flux:badge>
                        @endunless

                        <flux:button size="sm" variant="ghost" icon="arrow-up" wire:click="moveGroup({{ $group->id }}, 'up')" :aria-label="__('Move group up')" />
                        <flux:button size="sm" variant="ghost" icon="arrow-down" wire:click="moveGroup({{ $group->id }}, 'down')" :aria-label="__('Move group down')" />

                        @if ($group->overridden)
                            <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="restoreGroup({{ $group->id }})">
                                {{ __('Restore default') }}
                            </flux:button>
                        @endif

                        <flux:button size="sm" variant="ghost" :icon="$group->enabled ? 'eye-slash' : 'eye'" wire:click="toggleGroup({{ $group->id }})">
                            {{ $group->enabled ? __('Hide') : __('Show') }}
                        </flux:button>
                    </div>

                    <div class="space-y-2">
                        @foreach ($groupEntries as $entry)
                            <div wire:key="help-entry-{{ $entry->id }}" class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                <div class="min-w-0 flex-1">
                                    <flux:text class="truncate font-medium">{{ __($entry->content('title')) }}</flux:text>
                                    <flux:text class="truncate text-xs text-zinc-500">{{ $entry->slug }}</flux:text>
                                </div>

                                <flux:badge color="zinc">{{ $entry->type === 'faq' ? __('FAQ') : __('Guide') }}</flux:badge>

                                <flux:badge :color="$entry->overridden ? 'amber' : 'blue'">
                                    {{ $entry->overridden ? __('Edited by root') : __('Auto') }}
                                </flux:badge>

                                @unless ($entry->enabled)
                                    <flux:badge color="amber">{{ __('Hidden') }}</flux:badge>
                                @endunless

                                <flux:button size="sm" variant="ghost" icon="arrow-up" wire:click="moveEntry({{ $entry->id }}, 'up')" :aria-label="__('Move entry up')" />
                                <flux:button size="sm" variant="ghost" icon="arrow-down" wire:click="moveEntry({{ $entry->id }}, 'down')" :aria-label="__('Move entry down')" />

                                @if ($entry->overridden)
                                    <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="restoreEntry({{ $entry->id }})">
                                        {{ __('Restore default') }}
                                    </flux:button>
                                @endif

                                <flux:button size="sm" variant="ghost" :icon="$entry->enabled ? 'eye-slash' : 'eye'" wire:click="toggleEntry({{ $entry->id }})">
                                    {{ $entry->enabled ? __('Hide') : __('Show') }}
                                </flux:button>

                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEditor({{ $entry->id }})">
                                    {{ __('Edit') }}
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endforeach

            @if ($availableForSource !== [])
                <flux:card class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="space-y-1">
                            <flux:heading>{{ __('Available entries') }}</flux:heading>
                            <flux:text>{{ __('Declared by packages but not published in this install yet.') }}</flux:text>
                        </div>
                        <flux:badge color="zinc">{{ count($availableForSource) }}</flux:badge>
                    </div>

                    <div class="space-y-2">
                        @foreach ($availableForSource as $entry)
                            <div wire:key="available-{{ $entry['slug'] }}" class="flex flex-wrap items-center gap-3 rounded-lg border border-dashed border-zinc-300 px-3 py-2 dark:border-zinc-600">
                                <div class="min-w-0 flex-1">
                                    <flux:text class="truncate font-medium">{{ __($entry['title']) }}</flux:text>
                                    <flux:text class="truncate text-xs text-zinc-500">{{ $entry['slug'] }}</flux:text>
                                </div>
                                <flux:badge color="zinc">{{ $entry['type'] === 'faq' ? __('FAQ') : __('Guide') }}</flux:badge>
                                <flux:badge color="zinc">{{ __('Available') }}</flux:badge>
                                <flux:button size="sm" variant="primary" icon="plus" wire:click="publishEntry(@js($entry['slug']))">
                                    {{ __('Publish') }}
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif
        </div>
    @empty
        <flux:card class="space-y-3 text-center">
            <flux:icon icon="book-open" class="mx-auto size-8 text-zinc-400" />
            <flux:heading level="2">{{ __('No help content found.') }}</flux:heading>
            <flux:text>{{ __('Run help:sync to materialize the entries declared by packages.') }}</flux:text>
        </flux:card>
    @endforelse

    <flux:modal name="help-entry" wire:model.self="showEditor" @close="closeEditor" class="max-w-3xl space-y-6">
        <div class="space-y-1">
            <flux:heading size="lg">{{ __('Edit entry') }}</flux:heading>
            <flux:text class="text-xs">{{ $editingSlug }}</flux:text>
        </div>

        <div x-data="{ tab: 'es' }" class="space-y-4">
            <div class="flex gap-1 border-b border-zinc-200 dark:border-zinc-700" role="tablist">
                <button
                    type="button"
                    role="tab"
                    @click="tab = 'es'"
                    :aria-selected="tab === 'es'"
                    :class="tab === 'es' ? 'border-accent text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500'"
                    class="border-b-2 px-4 py-2 text-sm font-medium"
                >{{ __('Spanish') }}</button>
                <button
                    type="button"
                    role="tab"
                    @click="tab = 'en'"
                    :aria-selected="tab === 'en'"
                    :class="tab === 'en' ? 'border-accent text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500'"
                    class="border-b-2 px-4 py-2 text-sm font-medium"
                >{{ __('English') }}</button>
            </div>

            <div x-show="tab === 'es'" role="tabpanel" class="space-y-4">
                <flux:input wire:model="titleEs" :label="__('Title (Spanish)')" />
                <flux:input wire:model="summaryEs" :label="__('Summary (Spanish)')" />
                <flux:textarea wire:model="bodyEs" :label="__('Body (Spanish)')" rows="12" />
            </div>

            <div x-show="tab === 'en'" role="tabpanel" class="space-y-4" x-cloak>
                <flux:input wire:model="titleEn" :label="__('Title (English)')" />
                <flux:input wire:model="summaryEn" :label="__('Summary (English)')" />
                <flux:textarea wire:model="bodyEn" :label="__('Body (English)')" rows="12" />
            </div>
        </div>

        <flux:checkbox.group wire:model="roles" :label="__('Visible to')">
            @foreach ($roleOptions as $role)
                <flux:checkbox :value="$role->value" :label="__($role->label())" />
            @endforeach
        </flux:checkbox.group>
        <flux:text class="text-xs">{{ __('Leave every role unchecked to make the entry available to all authenticated accounts.') }}</flux:text>

        <div class="flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="closeEditor">{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" wire:click="save">{{ __('Save') }}</flux:button>
        </div>
    </flux:modal>
</section>
