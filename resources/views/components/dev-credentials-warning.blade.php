@php
    $rootUser = auth()->user();
@endphp

@if ($rootUser && \App\Support\DevRootCredentials::isActiveFor($rootUser))
    <flux:callout variant="warning" icon="exclamation-triangle" class="mb-6">
        <flux:callout.heading>{{ __('Development credentials in use') }}</flux:callout.heading>
        <flux:callout.text>
            {{ __('The root account is still using the default development password. Change it before deploying to production.') }}
            <flux:link :href="route('security.edit')" wire:navigate>{{ __('Change password') }}</flux:link>
        </flux:callout.text>
    </flux:callout>
@endif