@props([
    'label',
    'value',
    'change',
    'series' => [],
    'color' => 'accent',
])

@php
    $lineColor = match ($color) {
        'emerald' => 'text-emerald-600 dark:text-emerald-400',
        'amber' => 'text-amber-600 dark:text-amber-400',
        'rose' => 'text-rose-600 dark:text-rose-400',
        default => 'text-accent-content',
    };

    $values = array_values(array_map('floatval', array_filter($series, static fn ($point) => is_numeric($point) && is_finite((float) $point))));
    $path = null;

    if (count($values) >= 2) {
        $minimum = min($values);
        $maximum = max($values);
        $range = $maximum - $minimum;
        $coordinates = [];

        foreach ($values as $index => $point) {
            $coordinates[] = [
                $index * 128 / (count($values) - 1),
                $range > 0 ? 4 + ($maximum - $point) * 40 / $range : 24,
            ];
        }

        $format = static fn (float $number): string => number_format($number, 2, '.', '');
        $path = 'M '.$format($coordinates[0][0]).' '.$format($coordinates[0][1]);

        for ($index = 1; $index < count($coordinates); $index++) {
            [$previousX, $previousY] = $coordinates[$index - 1];
            [$x, $y] = $coordinates[$index];
            $controlOffset = ($x - $previousX) / 3;
            $path .= ' C '.$format($previousX + $controlOffset).' '.$format($previousY).' '.$format($x - $controlOffset).' '.$format($y).' '.$format($x).' '.$format($y);
        }
    }

    $gradientId = uniqid('trend-line-', true);
@endphp

<flux:card {{ $attributes->class('h-full min-w-0') }}>
    <div class="flex h-full min-w-0 items-center justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $label }}</p>
            <p class="mt-3 text-3xl font-semibold leading-none tabular-nums text-zinc-900 dark:text-zinc-100">{{ $value }}</p>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $change }}</p>
        </div>

        @if ($path)
            <svg class="h-12 w-24 shrink-0 overflow-visible sm:w-32 {{ $lineColor }}" viewBox="0 0 128 48" fill="none" aria-hidden="true">
                <defs>
                    <linearGradient id="{{ $gradientId }}" x1="0" x2="128" gradientUnits="userSpaceOnUse">
                        <stop stop-color="currentColor" stop-opacity=".55" />
                        <stop offset=".5" stop-color="currentColor" />
                        <stop offset="1" stop-color="currentColor" stop-opacity=".55" />
                    </linearGradient>
                </defs>
                <path d="{{ $path }}" stroke="url(#{{ $gradientId }})" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        @endif
    </div>
</flux:card>
