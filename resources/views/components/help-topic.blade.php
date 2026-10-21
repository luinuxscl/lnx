<flux:tooltip :content="$entryTitle" position="bottom">
    <flux:button
        size="sm"
        variant="ghost"
        icon="lifebuoy"
        :href="route('help.index', ['topic' => $slug])"
        wire:navigate
        :aria-label="$ariaLabel"
    >
        <span class="max-sm:sr-only">{{ __('Read the guide') }}</span>
    </flux:button>
</flux:tooltip>
