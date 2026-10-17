<?php

namespace App\Mail;

use App\Models\OutboundEmail;
use App\Models\User;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\RawMessage;
use Throwable;

/**
 * Persists a record of every outbound email attempt.
 *
 * The record keeps only stable, non-sensitive fields: recipient, subject,
 * outcome and the exception class on failure. Exception messages are never
 * stored because they can leak transport credentials.
 */
class OutboundEmailRecorder
{
    public function record(RawMessage $message, string $status, ?Throwable $error = null): void
    {
        try {
            $email = $this->extractEmail($message);
            $to = $email?->getTo()[0] ?? null;

            if ($to === null) {
                return;
            }

            $recipient = User::query()->where('email', $to->getAddress())->first();

            OutboundEmail::create([
                'user_id' => $recipient?->id,
                'to_email' => $to->getAddress(),
                'to_name' => $to->getName() !== '' ? $to->getName() : null,
                'subject' => $email->getSubject(),
                'status' => $status,
                'error' => $error === null ? null : class_basename($error),
                'sent_at' => $status === OutboundEmail::STATUS_SENT ? now() : null,
            ]);
        } catch (Throwable) {
            // Recording must never interfere with sending mail.
        }
    }

    private function extractEmail(RawMessage $message): ?Email
    {
        if ($message instanceof Email) {
            return $message;
        }

        if ($message instanceof Message) {
            return MessageConverter::toEmail($message);
        }

        return null;
    }
}
