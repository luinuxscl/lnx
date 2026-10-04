<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalConversation extends Model
{
    public const TYPE_DIRECT = 'direct';

    public const TYPE_GROUP = 'group';

    public const TYPE_ANNOUNCEMENT = 'announcement';

    public const TYPE_SUPPORT = 'support';

    public const TYPES = [
        self::TYPE_DIRECT,
        self::TYPE_GROUP,
        self::TYPE_ANNOUNCEMENT,
        self::TYPE_SUPPORT,
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /** @return HasMany<InternalConversationParticipant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(InternalConversationParticipant::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'internal_conversation_participants')
            ->withPivot(['role', 'last_read_at'])
            ->withTimestamps();
    }

    /** @return HasMany<InternalMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(InternalMessage::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<SupportTicket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function hasParticipant(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->participants()->where('user_id', $userId)->exists();
    }

    public function participantFor(User|int $user): ?InternalConversationParticipant
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->participants->firstWhere('user_id', $userId)
            ?? $this->participants()->where('user_id', $userId)->first();
    }

    public function isDirect(): bool
    {
        return $this->type === self::TYPE_DIRECT;
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }

    public function isAnnouncement(): bool
    {
        return $this->type === self::TYPE_ANNOUNCEMENT;
    }

    public function isSupport(): bool
    {
        return $this->type === self::TYPE_SUPPORT;
    }

    public function titleFor(User $viewer): string
    {
        if ($this->isDirect()) {
            $other = $this->users->firstWhere('id', '!==', $viewer->id);
            if (! $other) {
                $other = $this->users()->where('users.id', '!=', $viewer->id)->first();
            }

            return $other?->name ?? __('Unknown user');
        }

        if ($this->isSupport() && $this->ticket) {
            return __('Support Ticket #:id - :title', [
                'id' => $this->ticket->id,
                'title' => $this->ticket->title,
            ]);
        }

        return $this->title ?? __('Conversation');
    }

    public function unreadCountFor(User $user): int
    {
        $participant = $this->participantFor($user);
        if (! $participant) {
            return 0;
        }

        $query = $this->messages()->where('user_id', '!=', $user->id);

        if ($participant->last_read_at !== null) {
            $query->where('created_at', '>', $participant->last_read_at);
        }

        return $query->count();
    }
}
