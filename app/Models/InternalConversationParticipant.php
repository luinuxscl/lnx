<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalConversationParticipant extends Model
{
    public const ROLE_CREATOR = 'creator';

    public const ROLE_MEMBER = 'member';

    public const ROLE_RECIPIENT = 'recipient';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<InternalConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(InternalConversation::class, 'internal_conversation_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
