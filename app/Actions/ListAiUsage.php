<?php

namespace App\Actions;

use App\Models\AiUsageEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Filters and aggregates the AI consumption record for the root-only panel.
 */
class ListAiUsage
{
    /**
     * Validation rules shared by the Livewire page.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'user' => ['nullable', 'string', 'max:255'],
            'application' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:'.implode(',', [
                AiUsageEvent::STATUS_SUCCESS,
                AiUsageEvent::STATUS_ERROR,
                AiUsageEvent::STATUS_BLOCKED,
            ])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * Human readable attribute names for validation messages.
     *
     * @return array<string, string>
     */
    public static function attributeNames(): array
    {
        return [
            'user' => __('User'),
            'application' => __('Application'),
            'model' => __('Model'),
            'status' => __('Status'),
            'from' => __('From'),
            'to' => __('To'),
        ];
    }

    /**
     * Filter and paginate the consumption record.
     *
     * @param  array{user?: string, application?: string, model?: string, status?: string, from?: string, to?: string}  $filters
     * @return LengthAwarePaginator<int, AiUsageEvent>
     *
     * @throws ValidationException
     */
    public function handle(array $filters, int $perPage = 15, ?int $page = null): LengthAwarePaginator
    {
        return $this->query($filters)
            ->orderByDesc('recorded_at')->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Aggregate totals for the current filters.
     *
     * @param  array{user?: string, application?: string, model?: string, status?: string, from?: string, to?: string}  $filters
     * @return array{events: int, tokens: int, reported_cost: float, estimated_cost: float, blocked: int, errors: int, unknown_cost: int}
     *
     * @throws ValidationException
     */
    public function summary(array $filters): array
    {
        $row = (array) $this->query($filters)->toBase()
            ->selectRaw('COUNT(*) AS events')
            ->selectRaw('COALESCE(SUM(total_tokens), 0) AS tokens')
            ->selectRaw('COALESCE(SUM(reported_cost), 0) AS reported_cost')
            ->selectRaw('COALESCE(SUM(estimated_cost), 0) AS estimated_cost')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS blocked', [AiUsageEvent::STATUS_BLOCKED])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS errors', [AiUsageEvent::STATUS_ERROR])
            ->selectRaw('SUM(CASE WHEN status != ? AND cost_source = ? THEN 1 ELSE 0 END) AS unknown_cost', [
                AiUsageEvent::STATUS_BLOCKED,
                AiUsageEvent::COST_UNKNOWN,
            ])
            ->first();

        return [
            'events' => (int) ($row['events'] ?? 0),
            'tokens' => (int) ($row['tokens'] ?? 0),
            'reported_cost' => (float) ($row['reported_cost'] ?? 0),
            'estimated_cost' => (float) ($row['estimated_cost'] ?? 0),
            'blocked' => (int) ($row['blocked'] ?? 0),
            'errors' => (int) ($row['errors'] ?? 0),
            'unknown_cost' => (int) ($row['unknown_cost'] ?? 0),
        ];
    }

    /**
     * Build the filtered query, validating the filters first.
     *
     * @param  array{user?: string, application?: string, model?: string, status?: string, from?: string, to?: string}  $filters
     * @return Builder<AiUsageEvent>
     *
     * @throws ValidationException
     */
    protected function query(array $filters): Builder
    {
        Validator::make($filters, self::rules(), [], self::attributeNames())->validate();

        $user = (string) ($filters['user'] ?? '');
        $application = (string) ($filters['application'] ?? '');
        $model = (string) ($filters['model'] ?? '');
        $status = (string) ($filters['status'] ?? '');
        $from = (string) ($filters['from'] ?? '');
        $to = (string) ($filters['to'] ?? '');
        $timezone = (string) config('app.timezone');

        return AiUsageEvent::query()
            ->when($user !== '', function (Builder $query) use ($user): void {
                $term = '%'.addcslashes($user, '%_\\').'%';
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('user_name', 'like', $term)
                        ->orWhere('user_email', 'like', $term)
                        ->orWhereRaw('CAST(user_id AS CHAR) LIKE ?', [$term]);
                });
            })
            ->when($application !== '', fn (Builder $query) => $query->where('application', $application))
            ->when($model !== '', function (Builder $query) use ($model): void {
                $term = '%'.addcslashes($model, '%_\\').'%';
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('requested_model', 'like', $term)
                        ->orWhere('effective_model', 'like', $term);
                });
            })
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($from !== '', fn (Builder $query) => $query->where('recorded_at', '>=', Carbon::createFromFormat('!Y-m-d', $from, $timezone)->startOfDay()))
            ->when($to !== '', fn (Builder $query) => $query->where('recorded_at', '<=', Carbon::createFromFormat('!Y-m-d', $to, $timezone)->endOfDay()));
    }
}
