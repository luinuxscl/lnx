<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalMessage extends Model
{
    public const SENDER_USER = 'user';

    public const SENDER_SYSTEM = 'system';

    public const SENDER_AI = 'ai';

    public const SENDERS = [
        self::SENDER_USER,
        self::SENDER_SYSTEM,
        self::SENDER_AI,
    ];

    protected $guarded = [];

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

    /** @return BelongsTo<SupportMessage, $this> */
    public function supportMessage(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class);
    }

    public function isUser(): bool
    {
        return $this->sender_type === self::SENDER_USER;
    }

    public function isSystem(): bool
    {
        return $this->sender_type === self::SENDER_SYSTEM;
    }

    public function isAi(): bool
    {
        return $this->sender_type === self::SENDER_AI;
    }

    public function displaySenderName(): string
    {
        if ($this->isSystem()) {
            return $this->sender_name ?? __('System');
        }

        if ($this->isAi()) {
            $name = $this->sender_name ?? __('AI Agent');
            if ($this->user) {
                return __(':agent (on behalf of :user)', [
                    'agent' => $name,
                    'user' => $this->user->name,
                ]);
            }

            return $name;
        }

        return $this->user?->name ?? __('Unknown user');
    }
}
