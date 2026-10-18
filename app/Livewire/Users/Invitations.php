<?php

namespace App\Livewire\Users;

use App\Actions\IssueUserInvitation;
use App\Actions\RecordAdministrativeActivity;
use App\Actions\SearchInvitations;
use App\Enums\AdministrativeAction;
use App\Models\UserInvitation;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Invitations')]
class Invitations extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $status = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    #[Url(as: 'per')]
    public int $perPage = 10;

    public function mount(): void
    {
        $this->normalizeStatus();
        $this->normalizeSort();
        $this->normalizePerPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->normalizeStatus();
    }

    public function updatedPerPage(): void
    {
        $this->normalizePerPage();
        $this->resetPage();
    }

    private function normalizeStatus(): void
    {
        if (! in_array($this->status, SearchInvitations::STATUSES, true)) {
            $this->status = 'all';
        }
    }

    private function normalizeSort(): void
    {
        if (! array_key_exists($this->sortBy, SearchInvitations::SORTS)) {
            $this->sortBy = 'created';
        }

        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = 'desc';
        }
    }

    private function normalizePerPage(): void
    {
        if (! in_array($this->perPage, SearchInvitations::PER_PAGE, true)) {
            $this->perPage = SearchInvitations::PER_PAGE[0];
        }
    }

    /**
     * Toggle the ordering of the given sortable column.
     */
    public function sort(string $column): void
    {
        if (! array_key_exists($column, SearchInvitations::SORTS)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Determine whether any filter is currently narrowing the list.
     */
    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->status !== 'all';
    }

    /**
     * Reset every filter and return to the first page.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = 'all';
        $this->resetPage();
    }

    public function resend(UserInvitation $invitation, IssueUserInvitation $issue): void
    {
        $this->authorize('update', $invitation);

        if (! $issue->resend(auth()->user(), $invitation)) {
            $this->addError('invitation', __('The invitation was saved, but the email could not be sent. Reissue it from the invitation list.'));

            return;
        }

        Flux::toast(variant: 'success', text: __('Invitation sent.'));
    }

    public function revoke(UserInvitation $invitation): void
    {
        $this->authorize('update', $invitation);

        $updated = DB::transaction(function () use ($invitation): int {
            $current = UserInvitation::query()->whereKey($invitation->id)->firstOrFail();
            $updated = UserInvitation::query()->whereKey($invitation->id)
                ->whereNull('accepted_at')->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'pending_email' => null]);
            if ($updated === 1) {
                app(RecordAdministrativeActivity::class)->handle(auth()->user(), AdministrativeAction::InvitationRevoked, $current, ['revoked_at' => null], ['revoked_at' => $current->fresh()->revoked_at?->toIso8601String()]);
            }

            return $updated;
        });

        abort_unless($updated === 1, 409);
        Flux::toast(variant: 'success', text: __('Invitation revoked.'));
    }

    public function render(): View
    {
        $this->authorize('viewAny', UserInvitation::class);
        $this->normalizeStatus();
        $this->normalizeSort();
        $this->normalizePerPage();

        return view('livewire.users.invitations', [
            'invitations' => app(SearchInvitations::class)->handle(
                search: $this->search,
                status: $this->status,
                perPage: $this->perPage,
                sortBy: $this->sortBy,
                sortDirection: $this->sortDirection,
            ),
            'stats' => $this->stats(),
            'perPageOptions' => SearchInvitations::PER_PAGE,
        ])->layout('layouts.app');
    }

    /**
     * Headline counters shown above the list.
     *
     * @return array{pending: int, accepted: int, expired: int, revoked: int, sendFailed: int}
     */
    private function stats(): array
    {
        $now = now();

        return [
            'pending' => UserInvitation::query()
                ->whereNull('accepted_at')->whereNull('revoked_at')
                ->where('expires_at', '>', $now)->whereNull('send_failed_at')->count(),
            'accepted' => UserInvitation::query()->whereNotNull('accepted_at')->count(),
            'expired' => UserInvitation::query()
                ->whereNull('accepted_at')->whereNull('revoked_at')
                ->where('expires_at', '<=', $now)->count(),
            'revoked' => UserInvitation::query()
                ->whereNull('accepted_at')->whereNotNull('revoked_at')->count(),
            'sendFailed' => UserInvitation::query()
                ->whereNull('accepted_at')->whereNull('revoked_at')
                ->where('expires_at', '>', $now)->whereNotNull('send_failed_at')->count(),
        ];
    }
}
