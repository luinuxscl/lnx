@php
    $rootUser = auth()->user();
@endphp

@if ($rootUser && \App\Support\DevRootCredentials::isActiveFor($rootUser))
    <div
        x-data="{
            shown: true,
            key: 'dev-credentials-warning.snoozed-until',
            init() {
                const until = Number(window.localStorage.getItem(this.key) ?? 0);

                this.shown = Date.now() >= until;
            },
            dismiss() {
                window.localStorage.setItem(this.key, String(Date.now() + 60 * 60 * 1000));

                this.shown = false;
            },
        }"
        x-show="shown"
        x-cloak
    >
        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-6">
            <flux:callout.heading>{{ __('Development credentials in use') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('The root account is still using the default development password. Change it before deploying to production.') }}
                <flux:link :href="route('security.edit')" wire:navigate>{{ __('Change password') }}</flux:link>
            </flux:callout.text>
            <x-slot name="controls">
                <flux:button
                    variant="ghost"
                    size="sm"
                    square
                    icon="x-mark"
                    class="cursor-pointer"
                    x-on:click="dismiss()"
                    :aria-label="__('Dismiss')"
                    :title="__('Dismiss')"
                />
            </x-slot>
        </flux:callout>
    </div>
@endif