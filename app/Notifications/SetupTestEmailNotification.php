<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SetupTestEmailNotification extends Notification
{
    public function __construct(public string $language) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Setup test email', [], $this->language))
            ->line(__('This is a test message from :site to confirm that outgoing mail is configured.', ['site' => config('app.name')], $this->language))
            ->line(__('The mail transport accepted the message, which does not guarantee it reaches the inbox.', [], $this->language));
    }
}
