<?php

use App\Support\NumberFormat;
use Illuminate\Support\Number;

it('formats integers with dot as thousands separator', function () {
    expect(NumberFormat::format(1234567))->toBe('1.234.567')
        ->and(NumberFormat::format(1000))->toBe('1.000')
        ->and(NumberFormat::format(0))->toBe('0');
});

it('formats decimals with dot for thousands and comma for decimal separator', function () {
    expect(NumberFormat::format(1234567.89))->toBe('1.234.567,89')
        ->and(NumberFormat::format(1234.5))->toBe('1.234,5');
});

it('formats numbers with explicit precision', function () {
    expect(NumberFormat::format(1234.5678, precision: 4))->toBe('1.234,5678')
        ->and(NumberFormat::format(1234.5, precision: 2))->toBe('1.234,50')
        ->and(NumberFormat::format(0.000123, precision: 6))->toBe('0,000123');
});

it('handles null and empty values gracefully', function () {
    expect(NumberFormat::format(null))->toBe('—')
        ->and(NumberFormat::format(''))->toBe('—')
        ->and(NumberFormat::currency(null))->toBe('—');
});

it('formats currency with custom precision and currency symbol', function () {
    expect(NumberFormat::currency(1234.5678, 'USD', 4))->toBe('1.234,5678 USD')
        ->and(NumberFormat::currency(99.5, 'CLP', 0))->toBe('100 CLP');
});

it('formats file sizes using regional locale standards', function () {
    expect(NumberFormat::fileSize(null))->toBe('—')
        ->and(NumberFormat::fileSize(1048576))->toBe('1,0 MB');
});

it('ensures global Illuminate\\Support\\Number defaults to Spanish formatting rules', function () {
    expect(Number::format(1234567.89))->toBe('1.234.567,89');
});
