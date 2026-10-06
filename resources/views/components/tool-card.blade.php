@props([
    'title',
    'description',
    'icon',
    'href',
    'color' => 'accent',
    'external' => false,
])

@php
    $tone = match ($color) {
        'violet' => 'bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-400',
        'cyan' => 'bg-cyan-100 text-cyan-600 dark:bg-cyan-500/15 dark:text-cyan-400',
        'rose' => 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400',
        'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400',
        'blue' => 'bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400',
        'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
        default => 'bg-accent/10 text-accent-content',
    };
@endphp

<a
    href="{{ $href }}"
    @if (! $external) wire:navigate @endif
    @if ($external) target="_blank" rel="noopener noreferrer" @endif
    {{ $attributes->class('group block rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent') }}
>
    <flux:card class="flex h-full items-start gap-4 transition group-hover:border-zinc-300 group-hover:bg-zinc-50 dark:group-hover:border-zinc-600 dark:group-hover:bg-zinc-800/60">
        <span @class(['flex size-10 shrink-0 items-center justify-center rounded-md', $tone]) aria-hidden="true">
            <flux:icon :icon="$icon" class="size-5" />
        </span>

        <span class="min-w-0 flex-1">
            <flux:heading level="3" class="truncate">{{ $title }}</flux:heading>
            <flux:text class="mt-1 text-sm">{{ $description }}</flux:text>
        </span>

        <flux:icon
            :icon="$external ? 'arrow-up-right' : 'chevron-right'"
            class="size-4 shrink-0 text-zinc-400 transition group-hover:text-zinc-600 dark:group-hover:text-zinc-200"
            aria-hidden="true"
        />
    </flux:card>
</a>
