<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="flex flex-col">
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

        <x-dev-credentials-warning />

        {{ $slot }}

        <footer class="mt-auto flex flex-col gap-3 pt-8 text-xs text-zinc-500 sm:flex-row sm:items-center sm:justify-between dark:text-zinc-400">
            @if (filled(($site ?? null)?->footer_text))
                <span>{{ $site->footer_text }}</span>
            @endif

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <span class="inline-flex items-center gap-1">
                    {{ __('Coded with') }}
                    <flux:icon.heart variant="mini" class="size-4 text-rose-500" aria-hidden="true" />
                    {{ __('by') }}
                    <a href="https://like.cl" target="_blank" rel="noopener noreferrer" class="font-medium text-zinc-600 underline-offset-2 hover:underline focus-visible:underline dark:text-zinc-300">{{ __('Like Innovación') }}</a>
                </span>

                @if (auth()->user()->hasAnyRole([\App\Enums\Role::Admin, \App\Enums\Role::Root]))
                    <span>{{ __('LNX v:version', ['version' => config('app.version')]) }}</span>
                @endif
            </div>
        </footer>
    </flux:main>
</x-layouts::app.sidebar>
