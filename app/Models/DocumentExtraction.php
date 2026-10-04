<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $user_id
 * @property string $original_filename
 * @property string $file_path
 * @property int $file_size
 * @property string $mime_type
 * @property int|null $page_count
 * @property string|null $extracted_text
 * @property string $status
 * @property array<string, mixed>|null $extracted_data
 * @property array<string, mixed>|null $reviewed_data
 * @property array<string, mixed>|null $validation_notes
 * @property string|null $error_message
 * @property string|null $correlation_id
 * @property Carbon|null $processed_at
 * @property Carbon|null $confirmed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DocumentExtraction extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_REVIEW_REQUIRED = 'review_required';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'page_count',
        'extracted_text',
        'status',
        'extracted_data',
        'reviewed_data',
        'validation_notes',
        'error_message',
        'correlation_id',
        'processed_at',
        'confirmed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'extracted_data' => 'array',
            'reviewed_data' => 'array',
            'validation_notes' => 'array',
            'processed_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isReviewRequired(): bool
    {
        return $this->status === self::STATUS_REVIEW_REQUIRED;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
