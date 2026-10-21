<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string $role
 * @property string $content
 * @property int|null $tokens
 * @property string $status
 * @property array<int, array<string, mixed>>|null $citations
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AiChatMessage extends Model
{
    use HasUuids;

    protected $table = 'ai_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tokens',
        'status',
        'citations',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'citations' => 'array',
        ];
    }

    /**
     * @return BelongsTo<AiConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
