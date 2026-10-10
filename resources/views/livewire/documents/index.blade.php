<div class="flex flex-col gap-6 p-6">
    <x-lnx-ui::page-header
        :title="__('Document extraction')"
        :subtitle="__('Upload invoices or documents to automatically extract structured data using AI with human review.')"
        icon="document-text"
        color="cyan"
    />

    @if (session('status'))
        <flux:badge color="lime" size="lg" class="w-full justify-start p-4">
            {{ session('status') }}
        </flux:badge>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-2">{{ __('Upload new document') }}</flux:heading>
        <flux:text class="mb-4">{{ __('Supported formats: PDF (with selectable text), TXT, MD, JSON, CSV. Maximum file size: 10 MB.') }}</flux:text>

        <form wire:submit="upload" class="flex flex-col gap-4 max-w-xl">
            <div class="flex flex-col gap-2">
                <flux:input type="file" wire:model="file" label="{{ __('Select document file') }}" />
                @error('file')
                    <flux:text color="red" class="text-sm">{{ $message }}</flux:text>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <flux:button type="submit" variant="primary" icon="arrow-up-tray" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="upload">{{ __('Upload and extract') }}</span>
                    <span wire:loading wire:target="upload">{{ __('Uploading...') }}</span>
                </flux:button>
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Your documents') }}</flux:heading>

        @if ($documents->isEmpty())
            <flux:text class="italic">{{ __('No documents uploaded yet.') }}</flux:text>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-400">
                    <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        <tr>
                            <th scope="col" class="px-4 py-3">{{ __('File name') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Size') }}</th>
                            <th scope="col" class="px-4 py-3">{{ __('Date') }}</th>
                            <th scope="col" class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($documents as $doc)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="document-text" class="size-4 text-zinc-400" />
                                        <a href="{{ route('documents.show', $doc) }}" wire:navigate class="hover:underline">
                                            {{ $doc->original_filename }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($doc->isPending())
                                        <flux:badge color="zinc">{{ __('Pending') }}</flux:badge>
                                    @elseif ($doc->isProcessing())
                                        <flux:badge color="sky">{{ __('Processing') }}</flux:badge>
                                    @elseif ($doc->isReviewRequired())
                                        <flux:badge color="amber">{{ __('Review Required') }}</flux:badge>
                                    @elseif ($doc->isConfirmed())
                                        <flux:badge color="emerald">{{ __('Confirmed') }}</flux:badge>
                                    @elseif ($doc->isFailed())
                                        <flux:badge color="red">{{ __('Failed') }}</flux:badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    {{ \App\Support\NumberFormat::fileSize($doc->file_size) }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    {{ $doc->created_at->diffForHumans() }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button size="sm" variant="subtle" icon="eye" :href="route('documents.show', $doc)" wire:navigate>
                                            {{ __('Review') }}
                                        </flux:button>
                                        @if ($doc->isFailed())
                                            <flux:button size="sm" variant="subtle" icon="arrow-path" wire:click="reprocess('{{ $doc->id }}')">
                                                {{ __('Retry') }}
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
</div>
