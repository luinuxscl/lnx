@props([
    'sidebar' => false,
])

@php
    $siteName = ($site ?? null)?->title() ?? config('app.name', 'Laravel');
    $logoUrl = ($site ?? null)?->logoDarkUrl() ?? ($site ?? null)?->logoUrl();
@endphp

@if($sidebar)
    <flux:sidebar.brand :name="$siteName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="size-8 rounded-md object-contain" />
            @else
                <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            @endif
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="$siteName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="size-8 rounded-md object-contain" />
            @else
                <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            @endif
        </x-slot>
    </flux:brand>
@endif
