<?php

namespace App\Support;

use Illuminate\Support\Number;

class NumberFormat
{
    /**
     * Format a number using Spanish locale conventions: dot for thousands, comma for decimals.
     */
    public static function format(int|float|string|null $value, ?int $precision = null): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (! is_numeric($value)) {
            return (string) $value;
        }

        $numeric = (float) $value;

        if ($precision !== null) {
            return Number::format($numeric, precision: $precision, locale: 'es');
        }

        return Number::format($numeric, locale: 'es');
    }

    /**
     * Format a monetary amount or cost with numeric formatting and currency symbol/code.
     */
    public static function currency(int|float|string|null $value, string $currency = 'USD', int $precision = 4): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $formatted = static::format($value, precision: $precision);

        return "{$formatted} {$currency}";
    }

    /**
     * Format file size in KB/MB with regional numeric formatting.
     */
    public static function fileSize(int|float|null $bytes, int $precision = 1): string
    {
        if ($bytes === null) {
            return '—';
        }

        return Number::fileSize((int) $bytes, precision: $precision);
    }
}
