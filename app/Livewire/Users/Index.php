<?php

namespace App\Livewire\Users;

use App\Actions\ReactivateUser;
use App\Actions\RecordAdministrativeActivity;
use App\Actions\SearchUsers;
use App\Actions\SuspendUser;
use App\Enums\AdministrativeAction;
use App\Enums\Role;
use App\Models\User;
use App\Models\UserInvitation;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Users')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $status = 'all';

    #[Url(as: 'role')]
    public string $role = 'all';

    public bool $showStatusModal = false;

    #[Locked]
    public ?int $pendingUserId = null;

    #[Locked]
    public string $pendingUserName = '';

    #[Locked]
    public string $pendingStatusAction = '';

    public function mount(): void
    {
        $this->normalizeStatus();
        $this->normalizeRole();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->normalizeStatus();
    }

    public function updatingRole(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->normalizeRole();
    }

    private function normalizeStatus(): void
    {
        if (! in_array($this->status, SearchUsers::STATUSES, true)) {
            $this->status = 'all';
        }
    }

    private function normalizeRole(): void
    {
        if (! in_array($this->role, SearchUsers::ROLES, true)) {
            $this->role = 'all';
        }
    }

    /**
     * Determine whether any filter is currently narrowing the list.
     */
    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->status !== 'all' || $this->role !== 'all';
    }

    /**
     * Reset every filter and return to the first page.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = 'all';
        $this->role = 'all';
        $this->resetPage();
    }

    public function confirmStatusChange(User $user, string $action): void
    {
        abort_unless(in_array($action, ['suspend', 'reactivate'], true), 404);
        abort_if($user->is(auth()->user()) || $user->hasRole(Role::Root), 403);
        $this->authorize($action, $user);

        $this->pendingUserId = $user->id;
        $this->pendingUserName = $user->name;
        $this->pendingStatusAction = $action;
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->pendingUserId = null;
        $this->pendingUserName = '';
        $this->pendingStatusAction = '';
    }

    public function applyStatusChange(): void
    {
        abort_if($this->pendingUserId === null || ! in_array($this->pendingStatusAction, ['suspend', 'reactivate'], true), 404);

        $user = User::query()->whereKey($this->pendingUserId)->firstOrFail();

        if ($this->pendingStatusAction === 'suspend') {
            $this->suspendUser($user);
        } else {
            $this->reactivateUser($user);
        }

        $this->closeStatusModal();
    }

    public function suspendUser(User $user): void
    {
        app(SuspendUser::class)->handle(auth()->user(), $user);
        $this->resetPage();
        Flux::toast(variant: 'success', text: __('User suspended.'));
    }

    public function reactivateUser(User $user): void
    {
        app(ReactivateUser::class)->handle(auth()->user(), $user);
        $this->resetPage();
        Flux::toast(variant: 'success', text: __('User reactivated.'));
    }

    /**
     * Reset the pagination when the search term changes.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Delete the given user.
     */
    public function deleteUser(User $user): void
    {
        abort_if($user->is(auth()->user()), 403, 'You cannot delete your own account.');
        abort_if($user->hasRole(Role::Root), 403, 'Root accounts cannot be deleted.');

        $this->authorize('delete', $user);

        DB::transaction(function () use ($user): void {
            $current = User::query()->whereKey($user->id)->firstOrFail();
            abort_if($current->is(auth()->user()) || $current->hasRole(Role::Root), 403);
            $this->authorize('delete', $current);
            app(RecordAdministrativeActivity::class)->handle(auth()->user(), AdministrativeAction::UserDeleted, $current, ['name' => $current->name, 'email' => $current->email], []);
            $current->delete();
        });

        Flux::toast(variant: 'success', text: __('User deleted.'));
    }

    public function render(): View
    {
        $this->authorize('viewAny', User::class);
        $this->normalizeStatus();
        $this->normalizeRole();

        $users = app(SearchUsers::class)->handle($this->search, $this->status, $this->role);

        return view('livewire.users.index', [
            'users' => $users,
            'stats' => $this->stats(),
        ])->layout('layouts.app', [
            'breadcrumbs' => [
                ['label' => __('Users')],
            ],
        ]);
    }

    /**
     * Headline counters shown above the list.
     *
     * @return array{total: int, active: int, suspended: int, pendingInvitations: int}
     */
    private function stats(): array
    {
        return [
            'total' => User::query()->count(),
            'active' => User::query()->whereNull('suspended_at')->count(),
            'suspended' => User::query()->whereNotNull('suspended_at')->count(),
            'pendingInvitations' => UserInvitation::query()
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->count(),
        ];
    }
}
