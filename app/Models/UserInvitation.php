<?php

namespace App\Models;

use Database\Factories\UserInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $expires_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $send_failed_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $revoked_at
 */
#[Fillable(['email', 'pending_email', 'role', 'locale', 'invited_by', 'token_hash', 'expires_at', 'sent_at', 'send_failed_at', 'accepted_at', 'revoked_at'])]
#[Hidden(['token_hash'])]
class UserInvitation extends Model
{
    /** @use HasFactory<UserInvitationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'send_failed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->revoked_at === null && $this->expires_at->isFuture();
    }

    public function status(): string
    {
        if ($this->accepted_at !== null) {
            return 'Accepted';
        }
        if ($this->revoked_at !== null) {
            return 'Revoked';
        }
        if ($this->expires_at->isPast()) {
            return 'Expired';
        }

        return $this->send_failed_at !== null ? 'Send failed' : 'Pending';
    }
}
