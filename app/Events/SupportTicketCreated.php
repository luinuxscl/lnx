<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SupportTicketCreated
{
    use Dispatchable;

    public function __construct(public int $ticketId) {}
}
