<?php

namespace App\Listeners;

use App\Events\SupportPublicReplyAdded;
use App\Events\SupportTicketCreated;
use App\Events\SupportTicketStatusChanged;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Support\MessagingService;

class SyncSupportTicketToMessaging
{
    public function __construct(
        protected MessagingService $messagingService,
    ) {}

    public function handleTicketCreated(SupportTicketCreated $event): void
    {
        $ticket = SupportTicket::find($event->ticketId);
        if ($ticket) {
            $this->messagingService->ensureSupportConversation($ticket);
        }
    }

    public function handlePublicReplyAdded(SupportPublicReplyAdded $event): void
    {
        $ticket = SupportTicket::find($event->ticketId);
        $message = SupportMessage::find($event->messageId);

        if ($ticket && $message && ! $message->internal) {
            $this->messagingService->syncSupportReply($ticket, $message);
        }
    }

    public function handleStatusChanged(SupportTicketStatusChanged $event): void
    {
        $ticket = SupportTicket::find($event->ticketId);
        if ($ticket) {
            $this->messagingService->syncSupportStatusChange($ticket, $event->previous, $event->current);
        }
    }
}
