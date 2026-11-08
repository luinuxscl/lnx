<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $user_id
 * @property string $profile
 * @property string|null $title
 * @property string|null $model
 * @property string|null $system_context
 * @property string|null $system_prompt
 * @property int|null $assistant_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AiConversation extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'profile',
        'title',
        'model',
        'system_context',
        'system_prompt',
        'assistant_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<AiAssistant, $this>
     */
    public function assistant(): BelongsTo
    {
        return $this->belongsTo(AiAssistant::class, 'assistant_id');
    }

    /**
     * @return HasMany<AiChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'conversation_id');
    }

    /**
     * Last message of the thread, used by the conversation list.
     *
     * It orders instead of using `latestOfMany()`: `ai_messages.id` is a UUID
     * and PostgreSQL has no `max(uuid)`, so the aggregate that `latestOfMany()`
     * builds per column only works on SQLite. Lazy loading keeps `limit 1`, and
     * on a mixed-precision timestamp the ordered UUID breaks the tie.
     *
     * @return HasOne<AiChatMessage, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(AiChatMessage::class, 'conversation_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @return BelongsToMany<KnowledgeBase, $this>
     */
    public function knowledgeBases(): BelongsToMany
    {
        return $this->belongsToMany(
            KnowledgeBase::class,
            'ai_conversation_knowledge_base',
            'conversation_id',
            'knowledge_base_id',
        )->withTimestamps();
    }
}
