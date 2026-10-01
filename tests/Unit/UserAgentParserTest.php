<?php

use App\Support\UserAgentParser;

it('parses a user agent into browser, platform and device type', function (string $userAgent, string $browser, string $platform, bool $isDesktop) {
    $parsed = (new UserAgentParser)->parse($userAgent);

    expect($parsed)->toBe([
        'browser' => $browser,
        'platform' => $platform,
        'is_desktop' => $isDesktop,
    ]);
})->with([
    'chrome on windows' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Chrome',
        'Windows',
        true,
    ],
    'safari on iphone' => [
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        'Safari',
        'iOS',
        false,
    ],
    'firefox on linux' => [
        'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0',
        'Firefox',
        'Linux',
        true,
    ],
    'edge on windows' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
        'Microsoft Edge',
        'Windows',
        true,
    ],
    'android chrome' => [
        'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
        'Chrome',
        'Android',
        false,
    ],
]);

it('falls back to unknown values for an unrecognized user agent', function () {
    expect((new UserAgentParser)->parse('curl/8.5.0'))->toBe([
        'browser' => 'Unknown browser',
        'platform' => 'Unknown platform',
        'is_desktop' => true,
    ]);
});

it('handles a missing user agent', function () {
    expect((new UserAgentParser)->parse(null))->toBe([
        'browser' => 'Unknown browser',
        'platform' => 'Unknown platform',
        'is_desktop' => true,
    ]);
});
