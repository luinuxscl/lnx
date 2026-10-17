<?php

namespace App\Models;

use App\Enums\KnowledgeAudience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A reusable assistant preset: a system prompt, a default model and a set of
 * knowledge bases, scoped to an audience. Conversations may reference one and
 * inherit its configuration.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $system_prompt
 * @property string|null $model
 * @property KnowledgeAudience $audience
 * @property bool $is_active
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AiAssistant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'system_prompt',
        'model',
        'audience',
        'is_active',
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
     * @return HasMany<AiConversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(AiConversation::class, 'assistant_id');
    }

    /**
     * @return BelongsToMany<KnowledgeBase, $this>
     */
    public function knowledgeBases(): BelongsToMany
    {
        return $this->belongsToMany(
            KnowledgeBase::class,
            'ai_assistant_knowledge_base',
            'assistant_id',
            'knowledge_base_id',
        )->withTimestamps();
    }

    /**
     * Scope to the active presets a user is allowed to use.
     *
     * @param  Builder<AiAssistant>  $query
     * @return Builder<AiAssistant>
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
            ->whereIn('audience', $allowedAudiences);
    }

    /**
     * Whether the given user may use this preset.
     */
    public function isVisibleTo(User $user): bool
    {
        return $this->is_active && $this->audience->allows($user);
    }
}
