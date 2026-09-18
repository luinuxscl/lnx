<flux:dropdown position="bottom" align="end">
    <flux:tooltip :content="__('Language')" position="bottom">
        <flux:button
            variant="ghost"
            size="sm"
            icon="language"
            class="cursor-pointer"
            data-test="locale-switcher"
        >
            {{ strtoupper($locale) }}
        </flux:button>
    </flux:tooltip>

    <flux:menu>
        @foreach (config('app.available_locales') as $code => $label)
            <flux:menu.item
                wire:key="locale-{{ $code }}"
                wire:click="setLocale('{{ $code }}')"
                :icon="$locale === $code ? 'check' : null"
            >
                {{ $label }}
            </flux:menu.item>
        @endforeach
    </flux:menu>
</flux:dropdown>
