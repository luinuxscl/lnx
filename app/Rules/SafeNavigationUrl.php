<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a root-authored navigation destination: an internal relative path
 * or an absolute http(s) URL. Rejects javascript:, data:, protocol-relative
 * and URLs with embedded credentials to avoid stored XSS and open redirects.
 */
class SafeNavigationUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail(__('The destination must be a valid URL or internal path.'));

            return;
        }

        $value = trim($value);

        if (str_starts_with($value, '//')) {
            $fail(__('Protocol-relative URLs are not allowed.'));

            return;
        }

        if (str_starts_with($value, '/')) {
            return;
        }

        $parts = parse_url($value);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || empty($parts['host'])) {
            $fail(__('The destination must be an http(s) URL or an internal path.'));

            return;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            $fail(__('URLs with embedded credentials are not allowed.'));
        }
    }
}
