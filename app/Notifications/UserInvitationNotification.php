<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification
{
    public function __construct(public string $url, public string $language) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Invitation to join :site', ['site' => config('app.name')], $this->language))
            ->line(__('You have been invited to create an account.', [], $this->language))
            ->action(__('Accept invitation', [], $this->language), $this->url)
            ->line(__('This invitation expires in seven days.', [], $this->language));
    }
}
