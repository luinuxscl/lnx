<?php

namespace App\Livewire;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $isRoot = $user->hasRole(Role::Root);

        $canViewUsers = ! $isRoot
            && $user->hasAnyRole([Role::Root, Role::Admin])
            && Gate::allows('viewAny', User::class);
        $canViewInvitations = $canViewUsers && Gate::allows('viewAny', UserInvitation::class);
        $canCreateInvitations = $canViewInvitations && Gate::allows('create', UserInvitation::class);

        /** @var array{total: int, active: int, suspended: int, unverified: int, recent: int, admins: int}|null $userCounts */
        $userCounts = null;
        $pendingInvitations = null;

        if ($canViewUsers) {
            $counts = User::query()->selectRaw(
                'COUNT(*) AS total, '
                .'SUM(CASE WHEN suspended_at IS NULL THEN 1 ELSE 0 END) AS active, '
                .'SUM(CASE WHEN suspended_at IS NOT NULL THEN 1 ELSE 0 END) AS suspended, '
                .'SUM(CASE WHEN email_verified_at IS NULL THEN 1 ELSE 0 END) AS unverified, '
                .'SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS recent',
                [now()->subDays(7)],
            )->first()?->toArray() ?? [];

            $userCounts = [
                'total' => (int) ($counts['total'] ?? 0),
                'active' => (int) ($counts['active'] ?? 0),
                'suspended' => (int) ($counts['suspended'] ?? 0),
                'unverified' => (int) ($counts['unverified'] ?? 0),
                'recent' => (int) ($counts['recent'] ?? 0),
                'admins' => User::role(Role::Admin->value)->count(),
            ];
        }

        if ($canViewInvitations) {
            $pendingInvitations = UserInvitation::query()
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->count();
        }

        return view('livewire.dashboard', [
            'name' => $user->name,
            'isRoot' => $isRoot,
            'userCounts' => $userCounts,
            'pendingInvitations' => $pendingInvitations,
            'canCreateInvitations' => $canCreateInvitations,
            'unreadNotifications' => $user->unreadNotifications()->count(),
        ])->layout('layouts.app');
    }
}
