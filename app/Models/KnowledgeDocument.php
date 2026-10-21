<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $knowledge_base_id
 * @property string $title
 * @property string $source_type
 * @property string|null $help_slug
 * @property string|null $locale
 * @property string|null $file_path
 * @property string $content
 * @property string $status
 * @property string|null $error_message
 * @property int $chunk_count
 * @property Carbon|null $withdrawn_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class KnowledgeDocument extends Model
{
    protected $fillable = [
        'user_id',
        'knowledge_base_id',
        'title',
        'source_type',
        'help_slug',
        'locale',
        'file_path',
        'content',
        'status',
        'error_message',
        'chunk_count',
        'withdrawn_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chunk_count' => 'integer',
            'withdrawn_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<KnowledgeBase, $this>
     */
    public function base(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class, 'knowledge_base_id');
    }

    /**
     * @return HasMany<KnowledgeChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id');
    }

    /**
     * Scope query to documents accessible by the given user.
     *
     * A document that belongs to a knowledge base inherits the base audience.
     * A document without a base keeps the legacy ownership rules: root and
     * admin see everything, any other user sees their own documents and the
     * global ones (`user_id` null).
     *
     * @param  Builder<KnowledgeDocument>  $query
     * @return Builder<KnowledgeDocument>
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        return $query->whereNull('withdrawn_at')
            ->where(function (Builder $q) use ($user) {
                $q->whereHas('base', fn (Builder $base) => $base->visibleTo($user))
                    ->orWhere(function (Builder $legacy) use ($user) {
                        $legacy->whereNull('knowledge_base_id');

                        if (! $user->hasRole([Role::Root, Role::Admin])) {
                            $legacy->where(function (Builder $owner) use ($user) {
                                $owner->where('user_id', $user->id)->orWhereNull('user_id');
                            });
                        }
                    });
            });
    }

    /**
     * Scope query to documents that belong to any of the given bases.
     *
     * @param  Builder<KnowledgeDocument>  $query
     * @param  array<int, int>  $baseIds
     * @return Builder<KnowledgeDocument>
     */
    public function scopeInBases(Builder $query, array $baseIds): Builder
    {
        return $query->whereIn('knowledge_base_id', $baseIds);
    }

    /**
     * Scope query to indexed and active documents.
     *
     * @param  Builder<KnowledgeDocument>  $query
     * @return Builder<KnowledgeDocument>
     */
    public function scopeIndexed(Builder $query): Builder
    {
        return $query->where('status', 'indexed')
            ->whereNull('withdrawn_at');
    }
}
