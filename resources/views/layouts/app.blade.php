<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        <div class="mb-6 flex items-center gap-4 border-b border-zinc-200 pb-4 max-lg:hidden dark:border-zinc-700">
            <flux:breadcrumbs>
                @foreach (($breadcrumbs ?? []) as $item)
                    <flux:breadcrumbs.item :href="$item['href'] ?? null" wire:navigate>
                        {{ $item['label'] }}
                    </flux:breadcrumbs.item>
                @endforeach
            </flux:breadcrumbs>

            <flux:spacer />

            <flux:button
                x-data
                x-on:click="$flux.dark = ! $flux.dark"
                variant="ghost"
                size="sm"
                square
                class="cursor-pointer"
                :aria-label="__('Toggle dark mode')"
            >
                <flux:icon.sun x-show="! $flux.dark" variant="mini" />
                <flux:icon.moon x-show="$flux.dark" variant="mini" />
            </flux:button>

            <livewire:locale-switcher />
        </div>

        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
