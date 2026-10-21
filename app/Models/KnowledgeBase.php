<?php

namespace App\Models;

use App\Enums\KnowledgeAudience;
use Database\Factories\KnowledgeBaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property KnowledgeAudience $audience
 * @property bool $is_active
 * @property bool $is_system
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class KnowledgeBase extends Model
{
    /** @use HasFactory<KnowledgeBaseFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'audience',
        'is_active',
        'is_system',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => KnowledgeAudience::class,
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<KnowledgeDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(KnowledgeDocument::class, 'knowledge_base_id');
    }

    /**
     * @return BelongsToMany<AiConversation, $this>
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(
            AiConversation::class,
            'ai_conversation_knowledge_base',
            'knowledge_base_id',
            'conversation_id',
        )->withTimestamps();
    }

    /**
     * @return BelongsToMany<AiAssistant, $this>
     */
    public function assistants(): BelongsToMany
    {
        return $this->belongsToMany(
            AiAssistant::class,
            'ai_assistant_knowledge_base',
            'knowledge_base_id',
            'assistant_id',
        )->withTimestamps();
    }

    /**
     * Scope to the active bases a user is allowed to use.
     *
     * @param  Builder<KnowledgeBase>  $query
     * @return Builder<KnowledgeBase>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $allowedAudiences = array_map(
            fn (KnowledgeAudience $audience): string => $audience->value,
            array_filter(
                KnowledgeAudience::cases(),
                fn (KnowledgeAudience $audience): bool => $audience->allows($user),
            ),
        );

        return $query->where('is_active', true)
            ->where('is_system', false)
            ->whereIn('audience', $allowedAudiences);
    }

    /**
     * Whether the given user may use this base.
     */
    public function isVisibleTo(User $user): bool
    {
        return $this->is_active
            && ! $this->is_system
            && $this->audience->allows($user);
    }
}
