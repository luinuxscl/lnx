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

        $userCounts = null;
        $pendingInvitations = null;

        if ($canViewUsers) {
            $userCounts = User::query()->selectRaw('COUNT(*) AS total, SUM(CASE WHEN suspended_at IS NULL THEN 1 ELSE 0 END) AS active, SUM(CASE WHEN suspended_at IS NOT NULL THEN 1 ELSE 0 END) AS suspended')->first();
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
        ])->layout('layouts.app', [
            'breadcrumbs' => [['label' => __('Dashboard')]],
        ]);
    }
}
