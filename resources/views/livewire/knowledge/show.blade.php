<div class="flex flex-col gap-6 p-6">
    <x-lnx-ui::page-header
        :title="$document->title"
        :subtitle="__('Created :date by :user', ['date' => $document->created_at->diffForHumans(), 'user' => $document->user ? $document->user->name : __('Global')])"
        icon="book-open"
        color="emerald"
    >
        <x-slot:meta>
            @if ($document->status === 'indexed')
                <flux:badge color="emerald">{{ __('Indexed') }}</flux:badge>
            @elseif ($document->status === 'indexing')
                <flux:badge color="sky">{{ __('Indexing') }}</flux:badge>
            @elseif ($document->status === 'withdrawn')
                <flux:badge color="zinc">{{ __('Withdrawn') }}</flux:badge>
            @elseif ($document->status === 'failed')
                <flux:badge color="red">{{ __('Failed') }}</flux:badge>
            @else
                <flux:badge color="amber">{{ __('Draft') }}</flux:badge>
            @endif
        </x-slot:meta>

        <x-slot:actions>
            <flux:button variant="subtle" icon="arrow-path" wire:click="reindex">
                {{ __('Reindex') }}
            </flux:button>
            @if ($document->status !== 'withdrawn')
                <flux:button variant="subtle" color="amber" icon="archive-box" wire:click="withdraw" wire:confirm="{{ __('Withdraw this document? It will be excluded from knowledge searches immediately.') }}">
                    {{ __('Withdraw') }}
                </flux:button>
            @endif
        </x-slot:actions>
    </x-lnx-ui::page-header>

    @if ($document->error_message)
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-950/50 dark:text-red-200">
            <div class="flex items-center gap-2 font-medium">
                <flux:icon name="exclamation-triangle" class="size-5 text-red-500" />
                {{ __('Indexing Error') }}
            </div>
            <p class="mt-1 text-sm">{{ $document->error_message }}</p>
        </div>
    @endif

    <div class="grid grid-cols-12 gap-6">
        {{-- Left: Document Metadata & Full Content --}}
        <div class="col-span-12 lg:col-span-7 flex flex-col gap-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Full Content') }}</flux:heading>
                <div class="max-h-96 overflow-y-auto whitespace-pre-wrap rounded-lg bg-zinc-50 p-4 text-sm font-mono text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                    {{ $document->content }}
                </div>
            </div>
        </div>

        {{-- Right: Indexed Chunks --}}
        <div class="col-span-12 lg:col-span-5 flex flex-col gap-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">{{ __('Indexed Chunks') }} ({{ \Illuminate\Support\Number::format($document->chunk_count) }})</flux:heading>
                </div>

                @if ($document->chunks->isEmpty())
                    <flux:text class="italic">{{ __('No chunks created yet.') }}</flux:text>
                @else
                    <div class="flex flex-col gap-4 max-h-[500px] overflow-y-auto pr-2">
                        @foreach ($document->chunks as $chunk)
                            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                                <div class="flex items-center justify-between text-xs font-semibold text-zinc-500 dark:text-zinc-400 mb-2">
                                    <span>{{ __('Chunk #') }}{{ \Illuminate\Support\Number::format($chunk->chunk_index) }}</span>
                                    <span>{{ $chunk->tokens !== null ? \Illuminate\Support\Number::format($chunk->tokens) : 'N/A' }} {{ __('tokens') }}</span>
                                </div>
                                <div class="text-xs font-mono whitespace-pre-wrap text-zinc-700 dark:text-zinc-300">
                                    {{ $chunk->content }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
