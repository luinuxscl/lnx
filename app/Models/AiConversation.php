<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $user_id
 * @property string|null $title
 * @property string|null $model
 * @property string|null $system_context
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AiConversation extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'title',
        'model',
        'system_context',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AiChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'conversation_id');
    }
}
