<?php

namespace App\Livewire\Users;

use App\Actions\IssueUserInvitation;
use App\Models\UserInvitation;
use Flux\Flux;
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

    public function updatingSearch(): void
    {
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

        $updated = UserInvitation::query()->whereKey($invitation->id)
            ->whereNull('accepted_at')->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'pending_email' => null]);

        abort_unless($updated === 1, 409);
        Flux::toast(variant: 'success', text: __('Invitation revoked.'));
    }

    public function render(): View
    {
        $this->authorize('viewAny', UserInvitation::class);

        return view('livewire.users.invitations', [
            'invitations' => UserInvitation::query()
                ->when($this->search !== '', fn ($query) => $query->where('email', 'like', "%{$this->search}%"))
                ->orderByDesc('created_at')->orderByDesc('id')->paginate(10),
        ])->layout('layouts.app', [
            'breadcrumbs' => [
                ['label' => __('Users'), 'href' => route('admin.users.index')],
                ['label' => __('Invitations')],
            ],
        ]);
    }
}
