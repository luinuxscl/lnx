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
     * Search and paginate users.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(string $search, string $status = 'all', string $role = 'all', int $perPage = 10, ?int $page = null): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($status === 'active', fn ($query) => $query->whereNull('suspended_at'))
            ->when($status === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
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
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
