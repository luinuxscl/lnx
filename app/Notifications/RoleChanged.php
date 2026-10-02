<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class RoleChanged extends Notification
{
    public function __construct(
        public ?string $previousRole,
        public ?string $newRole,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{previous_role: string|null, new_role: string|null} */
    public function toDatabase(object $notifiable): array
    {
        return [
            'previous_role' => $this->previousRole,
            'new_role' => $this->newRole,
        ];
    }
}
