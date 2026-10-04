<?php

it('renders a trend line for two or more numeric values', function () {
    $view = $this->blade('<x-trend-card label="Sales" value="2,968" change="+3.2%" :series="$series" color="emerald" />', [
        'series' => [10, 20, 15],
    ]);

    $view->assertSee('Sales')
        ->assertSee('2,968')
        ->assertSee('<path d=', false);
});

it('keeps the metric without a trend line when the series is insufficient', function () {
    $view = $this->blade('<x-trend-card label="Sales" value="2,968" change="+3.2%" :series="$series" />', [
        'series' => [10, 'invalid'],
    ]);

    $view->assertSee('2,968')
        ->assertDontSee('<path d=', false);
});

it('escapes metric text supplied by the consumer', function () {
    $view = $this->blade('<x-trend-card :label="$label" value="2,968" change="+3.2%" />', [
        'label' => '<script>alert(1)</script>',
    ]);

    $view->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});
