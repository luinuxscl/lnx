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
 * @property string $title
 * @property string $source_type
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
        'title',
        'source_type',
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
     * @return HasMany<KnowledgeChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id');
    }

    /**
     * Scope query to documents accessible by the given user.
     *
     * @param  Builder<KnowledgeDocument>  $query
     * @return Builder<KnowledgeDocument>
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        return $query->whereNull('withdrawn_at')
            ->where(function (Builder $q) use ($user) {
                if ($user->hasRole([Role::Root, Role::Admin])) {
                    $q->whereNotNull('id'); // Root and Admin see all non-withdrawn documents
                } else {
                    $q->where('user_id', $user->id)
                        ->orWhereNull('user_id');
                }
            });
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
