<?php

use App\Ai\OpenRouter\StreamCostParser;

it('sums the cost reported across streamed chunks', function () {
    $body = implode("\n\n", [
        'data: '.json_encode(['choices' => [['delta' => ['content' => 'a']]]]),
        'data: '.json_encode(['usage' => ['cost' => 0.001]]),
        'data: '.json_encode(['usage' => ['cost' => 0.002]]),
        'data: [DONE]',
    ])."\n\n";

    expect((new StreamCostParser)->parse($body))->toEqualWithDelta(0.003, 0.0000001);
});

it('returns null when no cost is reported', function () {
    $body = 'data: '.json_encode(['usage' => ['total_tokens' => 5]])."\n\ndata: [DONE]\n\n";

    expect((new StreamCostParser)->parse($body))->toBeNull();
});

it('ignores comments, malformed payloads and non-data lines', function () {
    $body = ": comment\nevent: ping\ndata: not-json\ndata: ".json_encode(['usage' => ['cost' => 0.5]])."\n\n";

    expect((new StreamCostParser)->parse($body))->toBe(0.5);
});

it('treats a zero cost as reported', function () {
    $body = 'data: '.json_encode(['usage' => ['cost' => 0]])."\n\n";

    expect((new StreamCostParser)->parse($body))->toBe(0.0);
});

it('ignores a non-numeric cost', function () {
    $body = 'data: '.json_encode(['usage' => ['cost' => 'unknown']])."\n\n";

    expect((new StreamCostParser)->parse($body))->toBeNull();
});
