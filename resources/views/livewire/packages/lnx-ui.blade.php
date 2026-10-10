<section class="w-full">
    <x-lnx-ui::page-header
        title="lnx-ui"
        :subtitle="__('Check the installed version of the shared component package and update it when a newer one is available.')"
        icon="cube"
        :breadcrumbs="[['label' => __('Packages')]]"
    />

    @php
        $badge = match (true) {
            $checkError !== null => ['color' => 'red', 'icon' => 'exclamation-triangle', 'label' => __('Could not check for updates')],
            $latestVersion === null => ['color' => 'zinc', 'icon' => 'question-mark-circle', 'label' => __('Not checked')],
            $updateAvailable => ['color' => 'amber', 'icon' => 'arrow-up-circle', 'label' => __('Update available')],
            default => ['color' => 'green', 'icon' => 'check-circle', 'label' => __('Up to date')],
        };

        $errorMessage = match ($checkError) {
            'missing_token' => __('Set a GitHub token (GITHUB_TOKEN) to check for updates of the private repository.'),
            'no_tags' => __('The repository has no version tags yet.'),
            'request_failed' => __('The update check could not be completed. Check the network or the GitHub token.'),
            default => null,
        };
    @endphp

    <div class="mt-6 w-full space-y-6">
        <flux:card class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <flux:heading level="2" size="lg">{{ __('Versions') }}</flux:heading>
                    <flux:text class="mt-1 text-sm">{{ $packageName }}</flux:text>
                </div>

                <flux:badge :color="$badge['color']" :icon="$badge['icon']">{{ $badge['label'] }}</flux:badge>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Installed version') }}</dt>
                    <dd class="mt-1 font-mono text-lg tabular-nums">{{ $installedVersion }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Latest version') }}</dt>
                    <dd class="mt-1 font-mono text-lg tabular-nums">{{ $latestVersion ?? __('Not checked') }}</dd>
                </div>
            </dl>

            @if ($errorMessage !== null)
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.text>{{ $errorMessage }}</flux:callout.text>
                </flux:callout>
            @endif

            <div class="flex flex-wrap items-center gap-3">
                <flux:button icon="arrow-path" wire:click="checkForUpdates" wire:loading.attr="disabled" wire:target="checkForUpdates">
                    {{ __('Check for updates') }}
                </flux:button>

                @if ($checkedAt !== null)
                    <flux:text class="text-sm">
                        {{ __('Last checked: :time', ['time' => \Illuminate\Support\Carbon::parse($checkedAt)->diffForHumans()]) }}
                    </flux:text>
                @endif
            </div>
        </flux:card>

        @if ($updateAvailable)
            <flux:card class="space-y-3">
                <flux:heading level="2" size="lg">{{ __('Update available') }}</flux:heading>
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
                    <code class="min-w-0 flex-1 truncate rounded-md bg-zinc-100 px-3 py-2 font-mono text-sm dark:bg-zinc-800">{{ $updateCommand }}</code>

                    <flux:button
                        size="sm"
                        variant="ghost"
                        type="button"
                        x-on:click="copy(@js($updateCommand))"
                    >
                        <span x-show="! copied">{{ __('Copy') }}</span>
                        <span x-show="copied" x-cloak>{{ __('Copied') }}</span>
                    </flux:button>
                </div>
            </flux:card>
        @endif

        @if ($galleryEnabled)
            <section aria-label="{{ __('Design gallery') }}">
                @include('lnx-ui::design-gallery')
            </section>
        @endif
    </div>
</section>
