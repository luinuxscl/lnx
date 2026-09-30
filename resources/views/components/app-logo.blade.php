@props([
    'sidebar' => false,
])

@php
    $siteName = ($site ?? null)?->title() ?? config('app.name', 'Laravel');
    $siteTagline = ($site ?? null)?->site_tagline;
    $logoUrl = ($site ?? null)?->logoDarkUrl() ?? ($site ?? null)?->logoUrl();
@endphp

@if($sidebar)
    <a {{ $attributes->class('flex h-10 min-w-0 items-center gap-2 px-2 in-data-flux-sidebar-collapsed-desktop:w-10 in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:px-0 in-data-flux-sidebar-collapsed-desktop:in-data-flux-sidebar-active:absolute in-data-flux-sidebar-collapsed-desktop:in-data-flux-sidebar-active:opacity-0') }} aria-label="{{ $siteName }}" data-flux-sidebar-brand>
        <div class="flex aspect-square size-8 shrink-0 items-center justify-center overflow-hidden rounded-md {{ $logoUrl ? 'bg-transparent' : 'bg-accent-content' }} text-accent-foreground">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="size-8 rounded-md object-contain" />
            @else
                <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            @endif
        </div>

        <span class="flex min-w-0 flex-col in-data-flux-sidebar-collapsed-desktop:hidden">
            <span class="truncate text-sm leading-5 font-bold text-zinc-800 dark:text-zinc-100">{{ $siteName }}</span>
            @if (filled($siteTagline))
                <span class="truncate text-xs leading-4 font-medium text-zinc-500 dark:text-zinc-400">{{ $siteTagline }}</span>
            @endif
        </span>
    </a>
@else
    <flux:brand :name="$siteName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md {{ $logoUrl ? 'bg-transparent' : 'bg-accent-content' }} text-accent-foreground">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="size-8 rounded-md object-contain" />
            @else
                <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            @endif
        </x-slot>
    </flux:brand>
@endif
