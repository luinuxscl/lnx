<section class="w-full">
    <x-lnx-ui::page-header
        :title="__('Packages')"
        :subtitle="__('Installed packages and their available updates.')"
        icon="cube"
    >
        <x-slot:actions>
            <flux:button
                icon="arrow-path"
                wire:click="checkForUpdates"
                wire:loading.attr="disabled"
                wire:target="checkForUpdates"
            >
                {{ __('Check for updates') }}
            </flux:button>
        </x-slot:actions>
    </x-lnx-ui::page-header>

    @if (empty($packages))
        <div class="mt-6">
            <flux:card class="flex flex-col items-center justify-center py-12 text-center">
                <flux:icon.cube-transparent class="size-10 text-zinc-400" />
                <flux:heading level="2" size="lg" class="mt-4">{{ __('No packages are registered yet.') }}</flux:heading>
                <flux:text class="mt-1 max-w-md text-sm">
                    {{ __('A package appears here once its service provider declares it with the package registry.') }}
                </flux:text>
            </flux:card>
        </div>
    @else
        <div class="mt-6 w-full space-y-6">
            @foreach ($packages as $package)
                @php
                    $badge = match (true) {
                        $package['check_error'] !== null => ['color' => 'red', 'icon' => 'exclamation-triangle', 'label' => __('Could not check for updates')],
                        $package['latest_version'] === null => ['color' => 'zinc', 'icon' => 'question-mark-circle', 'label' => __('Not checked')],
                        $package['update_available'] => ['color' => 'amber', 'icon' => 'arrow-up-circle', 'label' => __('Update available')],
                        default => ['color' => 'green', 'icon' => 'check-circle', 'label' => __('Up to date')],
                    };

                    $errorMessage = match ($package['check_error']) {
                        'missing_token' => __('Set a GitHub token (GITHUB_TOKEN) to check for updates of the private repository.'),
                        'no_tags' => __('The repository has no version tags yet.'),
                        'request_failed' => __('The update check could not be completed. Check the network or the GitHub token.'),
                        default => null,
                    };
                @endphp

                <flux:card class="space-y-4" wire:key="package-{{ $package['name'] }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <flux:heading level="2" size="lg">{{ __($package['label']) }}</flux:heading>
                            <flux:text class="mt-1 font-mono text-sm">{{ $package['name'] }}</flux:text>
                            @if ($package['description'] !== null)
                                <flux:text class="mt-1 max-w-prose text-sm">{{ __($package['description']) }}</flux:text>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($package['url'] !== null)
                                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="$package['url']" target="_blank" rel="noopener noreferrer">
                                    {{ __('Repository') }}
                                </flux:button>
                            @endif
                            <flux:badge :color="$badge['color']" :icon="$badge['icon']">{{ $badge['label'] }}</flux:badge>
                        </div>
                    </div>

                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Installed version') }}</dt>
                            <dd class="mt-1 font-mono text-lg tabular-nums">{{ $package['installed_version'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Latest version') }}</dt>
                            <dd class="mt-1 font-mono text-lg tabular-nums">{{ $package['latest_version'] ?? __('Not checked') }}</dd>
                        </div>
                    </dl>

                    @if ($errorMessage !== null)
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            <flux:callout.text>{{ $errorMessage }}</flux:callout.text>
                        </flux:callout>
                    @endif

                    @if ($package['checked_at'] !== null)
                        <flux:text class="text-sm">
                            {{ __('Last checked: :time', ['time' => \Illuminate\Support\Carbon::parse($package['checked_at'])->diffForHumans()]) }}
                        </flux:text>
                    @endif

                    @if ($package['update_available'])
                        <div class="space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <flux:text>{{ __('Run this command to update the package:') }}</flux:text>

                            <div
                                x-data="{
                                    copied: false,
                                    copy(text) {
                                        navigator.clipboard?.writeText(text).catch(() => {});
                                        this.copied = true;
                                        setTimeout(() => this.copied = false, 1500);
                                    },
                                }"
                                class="flex items-center gap-2"
                            >
                                <code class="min-w-0 flex-1 truncate rounded-md bg-zinc-100 px-3 py-2 font-mono text-sm dark:bg-zinc-800">{{ $package['update_command'] }}</code>

                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    type="button"
                                    x-on:click="copy(@js($package['update_command']))"
                                >
                                    <span x-show="! copied">{{ __('Copy') }}</span>
                                    <span x-show="copied" x-cloak>{{ __('Copied') }}</span>
                                </flux:button>
                            </div>
                        </div>
                    @endif
                </flux:card>
            @endforeach
        </div>
    @endif
</section>
