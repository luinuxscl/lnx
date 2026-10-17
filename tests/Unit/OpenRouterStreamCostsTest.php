<?php

use App\Ai\OpenRouter\OpenRouterStreamCosts;

it('sums the cost of the steps that share an invocation id', function () {
    $costs = new OpenRouterStreamCosts;

    $costs->record('inv-1', 0.001);
    $costs->record('inv-1', 0.002);
    $costs->record('inv-2', 0.5);

    expect($costs->pull('inv-1'))->toEqualWithDelta(0.003, 0.0000001)
        ->and($costs->pull('inv-2'))->toBe(0.5);
});

it('reads and clears the cost so it does not leak', function () {
    $costs = new OpenRouterStreamCosts;

    $costs->record('inv-1', 0.25);

    expect($costs->pull('inv-1'))->toBe(0.25)
        ->and($costs->pull('inv-1'))->toBeNull()
        ->and($costs->pull('missing'))->toBeNull();
});
