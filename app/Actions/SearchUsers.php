<?php

namespace App\Actions;

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
     * Search and paginate users.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(string $search, string $status = 'all', int $perPage = 10, ?int $page = null): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($status === 'active', fn ($query) => $query->whereNull('suspended_at'))
            ->when($status === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
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
