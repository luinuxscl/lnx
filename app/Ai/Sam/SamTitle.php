<?php

namespace App\Ai\Sam;

/**
 * Deterministic conversation title.
 *
 * It is derived from the first message of a thread, never from the model:
 * redacted, whitespace-collapsed and truncated to a bounded length.
 */
final class SamTitle
{
    public const MAX_CHARS = 60;

    public static function from(?string $message, int $maxChars = self::MAX_CHARS): ?string
    {
        if ($message === null) {
            return null;
        }

        $text = SamRedactor::redact($message);
        $text = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $text) ?? '';
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if ($text === '') {
            return null;
        }

        $maxChars = max(1, $maxChars);

        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        $cut = mb_substr($text, 0, $maxChars);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace >= (int) floor($maxChars * 0.6)) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, " \t\n\r\0\x0B.,;:!?-").'…';
    }
}
