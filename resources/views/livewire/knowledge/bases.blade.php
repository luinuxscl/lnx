<div class="flex flex-col gap-6 p-6">
    <x-lnx-ui::page-header
        :title="__('Knowledge Bases')"
        :subtitle="__('Group documents into bases with an audience and attach them to chats.')"
        icon="rectangle-stack"
        color="emerald"
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                {{ __('Add base') }}
            </flux:button>
        </x-slot:actions>
    </x-lnx-ui::page-header>

    <div class="flex items-center justify-between gap-4">
        <div class="w-full max-w-sm">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search bases...') }}" icon="magnifying-glass" />
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        @if ($bases->isEmpty())
            <flux:text class="italic">{{ __('No knowledge bases found.') }}</flux:text>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-400">
                    <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        <tr>
                            <th scope="col" class="px-4 py-3">{{ __('Name') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Audience') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Documents') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($bases as $base)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                    <div class="flex flex-col">
                                        <span>{{ $base->name }}</span>
                                        @if ($base->description)
                                            <span class="text-xs text-zinc-500">{{ \Illuminate\Support\Str::limit($base->description, 80) }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <flux:badge color="zinc">{{ $base->audience->label() }}</flux:badge>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    {{ \Illuminate\Support\Number::format($base->documents_count) }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($base->is_active)
                                        <flux:badge color="emerald">{{ __('Active') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc">{{ __('Inactive') }}</flux:badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button size="sm" variant="subtle" icon="pencil-square" wire:click="edit({{ $base->id }})">
                                            {{ __('Edit') }}
                                        </flux:button>
                                        <flux:button size="sm" variant="subtle" icon="power" wire:click="toggleActive({{ $base->id }})">
                                            {{ $base->is_active ? __('Deactivate') : __('Activate') }}
                                        </flux:button>
                                        <flux:button size="sm" variant="subtle" color="red" icon="trash" wire:click="delete({{ $base->id }})" wire:confirm="{{ __('Delete this knowledge base? Documents keep their content but lose the base.') }}">
                                            {{ __('Delete') }}
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $bases->links() }}
            </div>
        @endif
    </div>

    <flux:modal wire:model="showModal" class="md:w-160">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingId ? __('Edit Knowledge Base') : __('Add Knowledge Base') }}</flux:heading>
                <flux:subheading>{{ __('A base groups documents and defines who can use them.') }}</flux:subheading>
            </div>

            <form wire:submit="save" class="space-y-4">
                <flux:input wire:model="name" label="{{ __('Name') }}" placeholder="{{ __('e.g. Operations Manual') }}" required />

                <flux:textarea wire:model="description" label="{{ __('Description') }}" rows="3" placeholder="{{ __('Optional summary of what this base contains.') }}" />

                <flux:select wire:model="audience" label="{{ __('Audience') }}">
                    @foreach ($audiences as $option)
                        <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:switch wire:model="is_active" label="{{ __('Active') }}" description="{{ __('Inactive bases are excluded from every search.') }}" />

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="$set('showModal', false)">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary" icon="check">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
