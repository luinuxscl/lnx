@props([
    'label',
    'value',
    'icon',
    'color' => 'accent',
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

<div {{ $attributes->class('flex min-w-0 items-center gap-4') }}>
    <span @class(['flex size-10 shrink-0 items-center justify-center rounded-md', $tone]) aria-hidden="true">
        <flux:icon :icon="$icon" class="size-5" />
    </span>

    <div class="min-w-0">
        <p class="truncate text-xl font-medium tabular-nums text-zinc-900 dark:text-zinc-100">{{ $value }}</p>
        <flux:text class="truncate text-sm">{{ $label }}</flux:text>
    </div>
</div>
