<?php

namespace App\Mail;

use App\Models\OutboundEmail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Throwable;

/**
 * Decorates the configured mail transport to record every send attempt.
 *
 * A successful send is recorded as `sent`; a transport failure is recorded as
 * `failed` and rethrown so the application keeps reporting the original error.
 */
class RecordingTransport implements TransportInterface
{
    public function __construct(
        private TransportInterface $transport,
        private OutboundEmailRecorder $recorder,
    ) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        try {
            $sent = $this->transport->send($message, $envelope);
        } catch (Throwable $error) {
            $this->recorder->record($message, OutboundEmail::STATUS_FAILED, $error);

            throw $error;
        }

        $this->recorder->record($message, OutboundEmail::STATUS_SENT);

        return $sent;
    }

    public function __toString(): string
    {
        return (string) $this->transport;
    }
}
