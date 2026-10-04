<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SupportTicketUpdated extends Notification
{
    public function __construct(public int $ticketId, public string $action) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{ticket_id: int, action: string} */
    public function toDatabase(object $notifiable): array
    {
        return ['ticket_id' => $this->ticketId, 'action' => $this->action];
    }
}
