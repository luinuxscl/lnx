<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchUsers
{
    /**
     * Allowed status filters for the user list.
     *
     * @var array<int, string>
     */
    public const STATUSES = ['all', 'active', 'suspended'];

    /**
     * Allowed role filters for the user list. "none" targets users without a role.
     *
     * @var array<int, string>
     */
    public const ROLES = ['all', 'root', 'admin', 'none'];

    /**
     * Allowed email verification filters.
     *
     * @var array<int, string>
     */
    public const VERIFICATIONS = ['all', 'verified', 'unverified'];

    /**
     * Sortable request keys mapped to their database columns.
     *
     * @var array<string, string>
     */
    public const SORTS = [
        'name' => 'name',
        'email' => 'email',
        'status' => 'suspended_at',
        'registered' => 'created_at',
    ];

    /**
     * Allowed page sizes.
     *
     * @var array<int, int>
     */
    public const PER_PAGE = [10, 25, 50];

    /**
     * Search, filter, sort and paginate users.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(
        string $search = '',
        string $status = 'all',
        string $role = 'all',
        int $perPage = 10,
        ?int $page = null,
        string $verification = 'all',
        string $sortBy = 'name',
        string $sortDirection = 'asc',
    ): LengthAwarePaginator {
        $column = self::SORTS[$sortBy] ?? 'name';
        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';

        return User::query()
            ->with('roles')
            ->when($status === 'active', fn ($query) => $query->whereNull('suspended_at'))
            ->when($status === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
            ->when($verification === 'verified', fn ($query) => $query->whereNotNull('email_verified_at'))
            ->when($verification === 'unverified', fn ($query) => $query->whereNull('email_verified_at'))
            ->when(
                in_array($role, [Role::Root->value, Role::Admin->value], true),
                fn ($query) => $query->whereHas('roles', fn ($query) => $query->where('name', $role)),
            )
            ->when($role === 'none', fn ($query) => $query->whereDoesntHave('roles'))
            ->when(
                $search !== '',
                fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")),
            )
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
