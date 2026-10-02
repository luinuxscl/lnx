<?php

use App\Ai\AiErrorCategorizer;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;

function requestException(int $status): RequestException
{
    return new RequestException(new Response(new Psr7Response($status)));
}

it('categorizes provider credit, rate and overload failures', function () {
    $categorizer = new AiErrorCategorizer;

    expect($categorizer->categorize(InsufficientCreditsException::forProvider('openrouter')))->toBe('credits')
        ->and($categorizer->categorize(RateLimitedException::forProvider('openrouter')))->toBe('rate_limited')
        ->and($categorizer->categorize(ProviderOverloadedException::forProvider('openrouter')))->toBe('unavailable');
});

it('distinguishes a timeout from other connection failures', function () {
    $categorizer = new AiErrorCategorizer;

    $timeout = ProviderConnectionException::forProvider('openrouter', 28, new ConnectionException('cURL error 28: Operation timed out'));
    $offline = ProviderConnectionException::forProvider('openrouter', 0, new ConnectionException('Could not resolve host'));

    expect($categorizer->categorize($timeout))->toBe('timeout')
        ->and($categorizer->categorize($offline))->toBe('unavailable');
});

it('maps provider HTTP statuses to categories', function () {
    $categorizer = new AiErrorCategorizer;

    expect($categorizer->categorize(requestException(401)))->toBe('authentication')
        ->and($categorizer->categorize(requestException(403)))->toBe('authentication')
        ->and($categorizer->categorize(requestException(402)))->toBe('credits')
        ->and($categorizer->categorize(requestException(404)))->toBe('model')
        ->and($categorizer->categorize(requestException(429)))->toBe('rate_limited')
        ->and($categorizer->categorize(requestException(503)))->toBe('unavailable')
        ->and($categorizer->categorize(requestException(418)))->toBe('unknown');
});
