<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="flex flex-col">
        <div class="mb-6 flex items-center gap-4 border-b border-zinc-200 pb-4 max-lg:hidden dark:border-zinc-700">
            @if (filled($breadcrumbs ?? []))
                <flux:breadcrumbs>
                    @foreach ($breadcrumbs as $item)
                        <flux:breadcrumbs.item :href="$item['href'] ?? null" wire:navigate>
                            {{ $item['label'] }}
                        </flux:breadcrumbs.item>
                    @endforeach
                </flux:breadcrumbs>
            @endif

            <flux:spacer />

            <flux:dropdown position="bottom" align="end">
                <flux:button type="button" variant="ghost" size="sm" icon="lifebuoy" icon-trailing="chevron-down">
                    {{ __('Support') }}
                </flux:button>
                <flux:menu>
                    <flux:menu.item icon="plus" x-data x-on:click="$dispatch('support-open')">{{ __('Create request') }}</flux:menu.item>
                    <flux:menu.item icon="list-bullet" :href="route('support.index', ['mine' => 1])" wire:navigate>{{ __('View my requests') }}</flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            @if (auth()->user()->hasRole(\App\Enums\Role::Root))
                <flux:button
                    variant="ghost"
                    size="sm"
                    square
                    icon="envelope"
                    :href="route('admin.emails.index')"
                    wire:navigate
                    class="cursor-pointer"
                    :aria-label="__('Sent emails')"
                    :title="__('Sent emails')"
                />
            @endif

            <livewire:notifications.bell />

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
            @if (filled(($site ?? null)?->footer()))
                <span>{{ $site->footer() }}</span>
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
