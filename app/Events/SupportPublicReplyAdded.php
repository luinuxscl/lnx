<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SupportPublicReplyAdded
{
    use Dispatchable;

    public function __construct(public int $ticketId, public int $messageId) {}
}
