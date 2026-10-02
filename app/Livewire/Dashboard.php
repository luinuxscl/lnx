<?php

namespace App\Livewire;

use App\Actions\DiagnoseSetup;
use App\Actions\ListAiUsage;
use App\Ai\AiConfig;
use App\Enums\Role;
use App\Models\Setting;
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
        $canViewUsers = $user->hasAnyRole([Role::Root, Role::Admin])
            && Gate::allows('viewAny', User::class);
        $canViewInvitations = $canViewUsers && Gate::allows('viewAny', UserInvitation::class);
        $canCreateInvitations = $canViewInvitations && Gate::allows('create', UserInvitation::class);
        $canViewSetup = $user->hasRole(Role::Root) && Gate::allows('viewAny', Setting::class);

        $userCounts = null;
        $pendingInvitations = null;
        $setup = null;
        $aiUsage = null;

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

        if ($canViewSetup) {
            $steps = app(DiagnoseSetup::class)->handle($user);
            $setup = [
                'total' => count($steps),
                'verified' => count(array_filter($steps, fn (array $step): bool => $step['status'] === 'verified')),
                'pending' => count(array_filter($steps, fn (array $step): bool => $step['status'] === 'pending')),
                'error' => count(array_filter($steps, fn (array $step): bool => $step['status'] === 'error')),
            ];

            $summary = app(ListAiUsage::class)->summary([
                'from' => now()->startOfMonth()->format('Y-m-d'),
                'to' => now()->endOfMonth()->format('Y-m-d'),
            ]);

            $aiUsage = [
                'events' => $summary['events'],
                'tokens' => $summary['tokens'],
                'reported_cost' => $summary['reported_cost'],
                'estimated_cost' => $summary['estimated_cost'],
                'unknown_cost' => $summary['unknown_cost'],
                'blocked' => $summary['blocked'],
                'currency' => app(AiConfig::class)->pricingCurrency(),
            ];
        }

        return view('livewire.dashboard', [
            'name' => $user->name,
            'userCounts' => $userCounts,
            'pendingInvitations' => $pendingInvitations,
            'canCreateInvitations' => $canCreateInvitations,
            'setup' => $setup,
            'aiUsage' => $aiUsage,
        ])->layout('layouts.app', [
            'breadcrumbs' => [['label' => __('Dashboard')]],
        ]);
    }
}
