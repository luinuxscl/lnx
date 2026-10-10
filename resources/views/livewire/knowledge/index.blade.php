<div class="flex flex-col gap-6 p-6">
    <x-lnx-ui::page-header
        :title="__('Knowledge Base')"
        :subtitle="__('Manage internal manuals, documents and procedures used for contextual RAG answers and verifiable references.')"
        icon="book-open"
        color="emerald"
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                {{ __('Add document') }}
            </flux:button>
        </x-slot:actions>
    </x-lnx-ui::page-header>

    <div class="flex items-center justify-between gap-4">
        <div class="w-full max-w-sm">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search documents...') }}" icon="magnifying-glass" />
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Knowledge Documents') }}</flux:heading>

        @if ($documents->isEmpty())
            <flux:text class="italic">{{ __('No knowledge documents found.') }}</flux:text>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-400">
                    <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        <tr>
                            <th scope="col" class="px-4 py-3">{{ __('Title') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Type') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Chunks') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Author') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Date') }}</th>
                            <th scope="col" class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($documents as $doc)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="book-open" class="size-4 text-zinc-400" />
                                        <a href="{{ route('knowledge.show', $doc) }}" wire:navigate class="hover:underline">
                                            {{ $doc->title }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs uppercase">
                                    <flux:badge color="zinc">{{ $doc->source_type }}</flux:badge>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($doc->status === 'indexed')
                                        <flux:badge color="emerald">{{ __('Indexed') }}</flux:badge>
                                    @elseif ($doc->status === 'indexing')
                                        <flux:badge color="sky">{{ __('Indexing') }}</flux:badge>
                                    @elseif ($doc->status === 'withdrawn')
                                        <flux:badge color="zinc">{{ __('Withdrawn') }}</flux:badge>
                                    @elseif ($doc->status === 'failed')
                                        <flux:badge color="red">{{ __('Failed') }}</flux:badge>
                                    @else
                                        <flux:badge color="amber">{{ __('Draft') }}</flux:badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    {{ \Illuminate\Support\Number::format($doc->chunk_count) }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    {{ $doc->user ? $doc->user->name : __('Global') }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    {{ $doc->updated_at->diffForHumans() }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button size="sm" variant="subtle" icon="eye" :href="route('knowledge.show', $doc)" wire:navigate>
                                            {{ __('View') }}
                                        </flux:button>
                                        <flux:button size="sm" variant="subtle" icon="arrow-path" wire:click="reindex('{{ $doc->id }}')">
                                            {{ __('Reindex') }}
                                        </flux:button>
                                        @if ($doc->status !== 'withdrawn')
                                            <flux:button size="sm" variant="subtle" color="amber" icon="archive-box" wire:click="withdraw('{{ $doc->id }}')" wire:confirm="{{ __('Withdraw this document? It will be excluded from knowledge searches immediately.') }}">
                                                {{ __('Withdraw') }}
                                            </flux:button>
                                        @endif
                                        <flux:button size="sm" variant="subtle" color="red" icon="trash" wire:click="delete('{{ $doc->id }}')" wire:confirm="{{ __('Are you sure you want to delete this document?') }}">
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
                {{ $documents->links() }}
            </div>
        @endif
    </div>

    {{-- Create Modal --}}
    <flux:modal wire:model="showCreateModal" class="md:w-160">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Add Knowledge Document') }}</flux:heading>
                <flux:subheading>{{ __('Provide text content or upload a text file to index into the knowledge base.') }}</flux:subheading>
            </div>

            <form wire:submit="createDocument" class="space-y-4">
                <flux:input wire:model="title" label="{{ __('Document Title') }}" placeholder="{{ __('e.g. Employee Handbook 2026') }}" required />

                <div class="flex items-center gap-4">
                    <flux:radio.group wire:model.live="sourceType" label="{{ __('Content Source') }}">
                        <flux:radio value="text" label="{{ __('Direct Text Entry') }}" />
                        <flux:radio value="file" label="{{ __('Text File Upload (.txt, .md, .csv)') }}" />
                    </flux:radio.group>
                </div>

                @if ($sourceType === 'file')
                    <div class="flex flex-col gap-2">
                        <flux:input type="file" wire:model="file" label="{{ __('Select File') }}" />
                        @error('file')
                            <flux:text color="red" class="text-sm">{{ $message }}</flux:text>
                        @enderror
                    </div>
                @else
                    <div class="flex flex-col gap-2">
                        <flux:textarea wire:model="content" label="{{ __('Document Content') }}" rows="8" placeholder="{{ __('Paste manual, procedures or guide text here...') }}" required />
                        @error('content')
                            <flux:text color="red" class="text-sm">{{ $message }}</flux:text>
                        @enderror
                    </div>
                @endif

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary" icon="arrow-up-tray" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="createDocument">{{ __('Save & Index') }}</span>
                        <span wire:loading wire:target="createDocument">{{ __('Processing...') }}</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
