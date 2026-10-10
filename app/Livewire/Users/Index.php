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
use Illuminate\Support\Collection;
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

    #[Url(as: 'verified')]
    public string $verification = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    #[Url(as: 'per')]
    public int $perPage = 10;

    /**
     * @var array<int, int|string>
     */
    public array $selected = [];

    public bool $showStatusModal = false;

    public bool $showBulkModal = false;

    #[Locked]
    public ?int $pendingUserId = null;

    #[Locked]
    public string $pendingUserName = '';

    #[Locked]
    public string $pendingStatusAction = '';

    #[Locked]
    public string $pendingBulkAction = '';

    public function mount(): void
    {
        $this->normalizeStatus();
        $this->normalizeRole();
        $this->normalizeVerification();
        $this->normalizeSort();
        $this->normalizePerPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedStatus(): void
    {
        $this->normalizeStatus();
    }

    public function updatingRole(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedRole(): void
    {
        $this->normalizeRole();
    }

    public function updatingVerification(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedVerification(): void
    {
        $this->normalizeVerification();
    }

    public function updatedPerPage(): void
    {
        $this->normalizePerPage();
        $this->resetPage();
        $this->clearSelection();
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

    private function normalizeVerification(): void
    {
        if (! in_array($this->verification, SearchUsers::VERIFICATIONS, true)) {
            $this->verification = 'all';
        }
    }

    private function normalizeSort(): void
    {
        if (! array_key_exists($this->sortBy, SearchUsers::SORTS)) {
            $this->sortBy = 'name';
        }

        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = 'asc';
        }
    }

    private function normalizePerPage(): void
    {
        if (! in_array($this->perPage, SearchUsers::PER_PAGE, true)) {
            $this->perPage = SearchUsers::PER_PAGE[0];
        }
    }

    /**
     * Toggle the ordering of the given sortable column.
     */
    public function sort(string $column): void
    {
        if (! array_key_exists($column, SearchUsers::SORTS)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
        $this->clearSelection();
    }

    /**
     * Determine whether any filter is currently narrowing the list.
     */
    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->status !== 'all'
            || $this->role !== 'all'
            || $this->verification !== 'all';
    }

    /**
     * Reset every filter and return to the first page.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = 'all';
        $this->role = 'all';
        $this->verification = 'all';
        $this->resetPage();
        $this->clearSelection();
    }

    /**
     * Build the export URL mirroring the current filters and sorting.
     */
    public function exportUrl(): string
    {
        return route('admin.users.export', array_filter([
            'q' => $this->search !== '' ? $this->search : null,
            'status' => $this->status !== 'all' ? $this->status : null,
            'role' => $this->role !== 'all' ? $this->role : null,
            'verified' => $this->verification !== 'all' ? $this->verification : null,
            'sort' => $this->sortBy,
            'dir' => $this->sortDirection,
        ], fn ($value) => $value !== null));
    }

    /**
     * Select every user on the current page.
     */
    public function selectPage(): void
    {
        $this->selected = $this->currentPageIds();
    }

    /**
     * Remove every selection.
     */
    public function clearSelection(): void
    {
        $this->selected = [];
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

    public function confirmBulkAction(string $action): void
    {
        abort_unless(in_array($action, ['suspend', 'reactivate', 'delete'], true), 404);
        abort_if(count($this->selected) === 0, 403);

        $this->pendingBulkAction = $action;
        $this->showBulkModal = true;
    }

    public function closeBulkModal(): void
    {
        $this->showBulkModal = false;
        $this->pendingBulkAction = '';
    }

    public function applyBulkAction(): void
    {
        abort_unless(in_array($this->pendingBulkAction, ['suspend', 'reactivate', 'delete'], true), 404);

        $ids = array_values(array_unique(array_map('intval', $this->selected)));
        $targets = User::query()->whereKey($ids)->get();

        $processed = 0;

        foreach ($targets as $user) {
            if ($user->is(auth()->user()) || $user->hasRole(Role::Root)) {
                continue;
            }

            match ($this->pendingBulkAction) {
                'suspend' => app(SuspendUser::class)->handle(auth()->user(), $user),
                'reactivate' => app(ReactivateUser::class)->handle(auth()->user(), $user),
                'delete' => $this->destroyUser($user),
                default => null,
            };

            $processed++;
        }

        $message = match ($this->pendingBulkAction) {
            'suspend' => __(':count users suspended.', ['count' => $processed]),
            'reactivate' => __(':count users reactivated.', ['count' => $processed]),
            'delete' => __(':count users deleted.', ['count' => $processed]),
            default => '',
        };

        $this->closeBulkModal();
        $this->clearSelection();
        $this->resetPage();

        Flux::toast(variant: 'success', text: $message);
    }

    public function suspendUser(User $user): void
    {
        app(SuspendUser::class)->handle(auth()->user(), $user);
        $this->clearSelection();
        $this->resetPage();
        Flux::toast(variant: 'success', text: __('User suspended.'));
    }

    public function reactivateUser(User $user): void
    {
        app(ReactivateUser::class)->handle(auth()->user(), $user);
        $this->clearSelection();
        $this->resetPage();
        Flux::toast(variant: 'success', text: __('User reactivated.'));
    }

    /**
     * Delete the given user.
     */
    public function deleteUser(User $user): void
    {
        $this->destroyUser($user);
        $this->clearSelection();
        Flux::toast(variant: 'success', text: __('User deleted.'));
    }

    /**
     * Delete a user inside a transaction, recording the administrative action.
     */
    private function destroyUser(User $user): void
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
    }

    public function render(): View
    {
        $this->authorize('viewAny', User::class);
        $this->normalizeStatus();
        $this->normalizeRole();
        $this->normalizeVerification();
        $this->normalizeSort();
        $this->normalizePerPage();

        $users = app(SearchUsers::class)->handle(
            search: $this->search,
            status: $this->status,
            role: $this->role,
            perPage: $this->perPage,
            verification: $this->verification,
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
        );

        return view('livewire.users.index', [
            'users' => $users,
            'stats' => $this->stats(),
            'pageIds' => $this->selectableIds($users->getCollection()),
            'perPageOptions' => SearchUsers::PER_PAGE,
        ])->layout('layouts.app');
    }

    /**
     * The user identifiers shown on the current page that can be selected.
     *
     * @return array<int, int>
     */
    private function currentPageIds(): array
    {
        $users = app(SearchUsers::class)->handle(
            search: $this->search,
            status: $this->status,
            role: $this->role,
            perPage: $this->perPage,
            page: $this->getPage(),
            verification: $this->verification,
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
        );

        return $this->selectableIds($users->getCollection());
    }

    /**
     * Filter out accounts that can never be acted on in bulk.
     *
     * @param  Collection<int, User>  $users
     * @return array<int, int>
     */
    private function selectableIds(Collection $users): array
    {
        return $users
            ->reject(fn (User $user) => $user->is(auth()->user()) || $user->hasRole(Role::Root))
            ->pluck('id')
            ->all();
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
