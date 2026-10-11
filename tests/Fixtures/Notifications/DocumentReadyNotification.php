<?php

namespace Tests\Fixtures\Notifications;

use Illuminate\Notifications\Notification;

/**
 * A notification a package would send about one of its own records. The kit
 * does not know it; the notification center renders it only because the
 * package declared the type in the registry.
 */
class DocumentReadyNotification extends Notification
{
    public function __construct(public int $documentId, public string $name) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{document_id: int, name: string} */
    public function toDatabase(object $notifiable): array
    {
        return ['document_id' => $this->documentId, 'name' => $this->name];
    }
}
