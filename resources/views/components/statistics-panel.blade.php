@props([
    'title',
    'updatedAt' => null,
])

<flux:card {{ $attributes->class('w-full space-y-4') }}>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <flux:heading level="2" size="xl">{{ $title }}</flux:heading>

        @if ($updatedAt)
            <flux:tooltip :content="$updatedAt" position="bottom">
                <span
                    tabindex="0"
                    role="img"
                    aria-label="{{ $updatedAt }}"
                    class="inline-flex size-8 cursor-help items-center justify-center rounded-md text-zinc-500 hover:text-zinc-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent dark:text-zinc-400 dark:hover:text-zinc-200"
                >
                    <flux:icon.arrow-path variant="mini" class="size-4" aria-hidden="true" />
                </span>
            </flux:tooltip>
        @endif
    </div>

    <div class="grid grid-cols-12 gap-6">
        {{ $slot }}
    </div>
</flux:card>
