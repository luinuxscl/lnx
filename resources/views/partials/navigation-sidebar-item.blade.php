@php($external = $item['external'])

@if ($external)
    <flux:sidebar.item
        :icon="$item['icon']"
        :href="$item['href']"
        :current="$item['current']"
        target="_blank"
        rel="noopener noreferrer"
    >
        {{ $item['label'] }}
    </flux:sidebar.item>
@elseif ($item['href'] !== null)
    <flux:sidebar.item
        :icon="$item['icon']"
        :href="$item['href']"
        :current="$item['current']"
        wire:navigate
    >
        {{ $item['label'] }}
    </flux:sidebar.item>
@else
    <flux:sidebar.item
        :icon="$item['icon']"
        :current="$item['current']"
    >
        {{ $item['label'] }}
    </flux:sidebar.item>
@endif
