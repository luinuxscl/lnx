<?php

namespace App\Ai\Sam;

/**
 * Keeps one-time confirmation phrases out of the conversation history.
 *
 * A phrase is `CONFIRM` plus a token. The module generates and validates it;
 * the runtime only makes sure it is never persisted nor treated as a message.
 */
final class SamRedactor
{
    public const PLACEHOLDER = 'CONFIRM ••••••••';

    /**
     * Replace any confirmation phrase with a neutral placeholder.
     */
    public static function redact(string $text): string
    {
        return (string) preg_replace(
            '/CONFIRM\s+[A-Z0-9]{4,32}/u',
            self::PLACEHOLDER,
            $text,
        );
    }

    /**
     * Whether the text contains a confirmation phrase anywhere.
     */
    public static function containsPhrase(string $text): bool
    {
        return preg_match('/CONFIRM\s+[A-Z0-9]{4,32}/u', $text) === 1;
    }

    /**
     * Whether the whole message is a confirmation phrase.
     *
     * Such a message is the confirmation input, not a conversation turn, so
     * it is never stored as a chat message.
     */
    public static function isConfirmationPhrase(string $text): bool
    {
        return preg_match('/^\s*CONFIRM\s+[A-Z0-9]{4,32}\s*$/iu', $text) === 1;
    }
}
