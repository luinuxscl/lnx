<?php

namespace App\Support;

class UserAgentParser
{
    /**
     * Parse a User-Agent string into a human-readable descriptor.
     *
     * @return array{browser: string, platform: string, is_desktop: bool}
     */
    public function parse(?string $userAgent): array
    {
        $userAgent ??= '';

        return [
            'browser' => $this->browser($userAgent),
            'platform' => $this->platform($userAgent),
            'is_desktop' => ! $this->isMobile($userAgent),
        ];
    }

    private function browser(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Microsoft Edge',
            str_contains($userAgent, 'OPR/'), str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Chrome'), str_contains($userAgent, 'CriOS') => 'Chrome',
            str_contains($userAgent, 'Firefox'), str_contains($userAgent, 'FxiOS') => 'Firefox',
            str_contains($userAgent, 'Safari') => 'Safari',
            default => 'Unknown browser',
        };
    }

    private function platform(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad'), str_contains($userAgent, 'iPod') => 'iOS',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Macintosh'), str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown platform',
        };
    }

    private function isMobile(string $userAgent): bool
    {
        return str_contains($userAgent, 'Mobile')
            || str_contains($userAgent, 'Android')
            || str_contains($userAgent, 'iPhone')
            || str_contains($userAgent, 'iPod');
    }
}
