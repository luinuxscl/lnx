<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">

        <div
            x-data="{
                themes: @js([
                    ['value' => 'orange', 'label' => __('Orange')],
                    ['value' => 'teal', 'label' => __('Teal')],
                    ['value' => 'rose', 'label' => __('Rose')],
                    ['value' => 'violet', 'label' => __('Violet')],
                    ['value' => 'blue', 'label' => __('Blue')],
                    ['value' => 'emerald', 'label' => __('Emerald')],
                ]),
                theme: localStorage.getItem('flux.theme') ?? 'orange',
                select(value) {
                    this.theme = value;

                    localStorage.setItem('flux.theme', value);

                    window.applyFluxTheme();
                },
            }"
        >
            <flux:heading size="sm" class="mb-2">{{ __('Theme') }}</flux:heading>
            <flux:text class="mb-4">{{ __('Select the accent color used throughout the application.') }}</flux:text>

            <div class="grid grid-cols-3 gap-3 sm:grid-cols-6">
                <template x-for="option in themes" :key="option.value">
                    <button
                        type="button"
                        @click="select(option.value)"
                        class="flex flex-col items-center gap-2 rounded-xl border p-3 transition"
                        :class="theme === option.value
                            ? 'border-accent/60 bg-accent/5 ring-2 ring-accent/40'
                            : 'border-zinc-200 hover:border-accent/60 dark:border-zinc-700'"
                        :aria-pressed="theme === option.value"
                    >
                        <span
                            class="size-7 rounded-full shadow-sm ring-2 ring-offset-2 ring-offset-white dark:ring-offset-zinc-900"
                            :class="theme === option.value ? 'ring-accent' : 'ring-zinc-200 dark:ring-zinc-700'"
                            :style="'background-color: var(--color-' + option.value + '-500)'"
                        ></span>
                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300" x-text="option.label"></span>
                    </button>
                </template>
            </div>
        </div>

        <flux:separator class="my-6" />

        <flux:heading size="sm" class="mb-2">{{ __('Color scheme') }}</flux:heading>
        <flux:text class="mb-4">{{ __('Select between light, dark and system color schemes.') }}</flux:text>

        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</section>