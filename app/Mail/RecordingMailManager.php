<?php

namespace App\Mail;

use Illuminate\Mail\MailManager;

/**
 * Mail manager that wraps every resolved mailer transport with a recorder.
 *
 * The transport is decorated once per mailer, so every message sent through
 * the application (notifications, password resets, package mail) is logged
 * into the outbound email log without touching the call sites.
 */
class RecordingMailManager extends MailManager
{
    /**
     * Attempt to get the mailer from the local cache, decorating its transport.
     */
    protected function get($name)
    {
        $mailer = parent::get($name);

        if (! $mailer->getSymfonyTransport() instanceof RecordingTransport) {
            $mailer->setSymfonyTransport(
                new RecordingTransport($mailer->getSymfonyTransport(), $this->app->make(OutboundEmailRecorder::class)),
            );
        }

        return $mailer;
    }
}
