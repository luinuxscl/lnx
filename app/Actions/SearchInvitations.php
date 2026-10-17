<?php

namespace App\Actions;

use App\Models\UserInvitation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchInvitations
{
    /**
     * Allowed status filters for the invitation list.
     *
     * @var array<int, string>
     */
    public const STATUSES = ['all', 'pending', 'accepted', 'expired', 'revoked', 'send_failed'];

    /**
     * Sortable request keys mapped to their database columns.
     *
     * @var array<string, string>
     */
    public const SORTS = [
        'email' => 'email',
        'role' => 'role',
        'expires' => 'expires_at',
        'created' => 'created_at',
    ];

    /**
     * Allowed page sizes.
     *
     * @var array<int, int>
     */
    public const PER_PAGE = [10, 25, 50];

    /**
     * Search, filter, sort and paginate user invitations.
     *
     * @return LengthAwarePaginator<int, UserInvitation>
     */
    public function handle(
        string $search = '',
        string $status = 'all',
        int $perPage = 10,
        ?int $page = null,
        string $sortBy = 'created',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        $column = self::SORTS[$sortBy] ?? 'created_at';
        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';

        return UserInvitation::query()
            ->when($search !== '', fn ($query) => $query->where('email', 'like', "%{$search}%"))
            ->when($status !== 'all', fn ($query) => $this->applyStatus($query, $status))
            ->orderBy($column, $direction)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Constrain the query to a single invitation state.
     *
     * The states mirror {@see UserInvitation::status()}: an accepted invitation
     * always wins, then revoked, then expired, and the remaining pending ones
     * are split by whether their last delivery failed.
     *
     * @param  Builder<UserInvitation>  $query
     * @return Builder<UserInvitation>
     */
    private function applyStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            'accepted' => $query->whereNotNull('accepted_at'),
            'revoked' => $query->whereNull('accepted_at')->whereNotNull('revoked_at'),
            'expired' => $query->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '<=', now()),
            'send_failed' => $query->whereNull('accepted_at')->whereNull('revoked_at')
                ->where('expires_at', '>', now())->whereNotNull('send_failed_at'),
            'pending' => $query->whereNull('accepted_at')->whereNull('revoked_at')
                ->where('expires_at', '>', now())->whereNull('send_failed_at'),
            default => $query,
        };
    }
}
