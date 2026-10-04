<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SupportTicketStatusChanged
{
    use Dispatchable;

    public function __construct(public int $ticketId, public string $previous, public string $current) {}
}
