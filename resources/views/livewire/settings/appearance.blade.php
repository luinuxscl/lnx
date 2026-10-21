<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <x-slot:help>
            <x-help-topic slug="lnx.preferences" />
        </x-slot:help>

        <flux:heading size="sm" class="mb-2">{{ __('Color scheme') }}</flux:heading>
        <flux:text class="mb-4">{{ __('Select between light, dark and system color schemes.') }}</flux:text>

        <flux:radio.group
            x-data="{ hydrated: false }"
            variant="segmented"
            x-model="$flux.appearance"
            x-effect="if (hydrated) { $wire.saveColorScheme($flux.appearance) } else { hydrated = true }"
        >
            <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
        </flux:radio.group>

        <flux:separator class="my-6" />

        <flux:heading size="sm" class="mb-2">{{ __('Language') }}</flux:heading>
        <flux:text class="mb-4">{{ __('Select the language used throughout the application.') }}</flux:text>

        <flux:select wire:model.live="locale" :aria-label="__('Language')">
            @foreach (config('app.available_locales') as $code => $label)
                <flux:select.option value="{{ $code }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:separator class="my-6" />

        <flux:heading size="sm" class="mb-2">{{ __('Time zone') }}</flux:heading>
        <flux:text class="mb-4">{{ __('Select the time zone used to display dates.') }}</flux:text>

        <flux:select wire:model.live="timezone" :aria-label="__('Time zone')">
            @foreach ($timezones as $timezone)
                <flux:select.option value="{{ $timezone }}">{{ $timezone }}</flux:select.option>
            @endforeach
        </flux:select>
    </x-settings.layout>
</section>