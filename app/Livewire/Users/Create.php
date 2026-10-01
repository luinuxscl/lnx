<?php

namespace App\Livewire\Users;

use App\Actions\IssueUserInvitation;
use App\Enums\Role;
use App\Models\UserInvitation;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Invite user')]
class Create extends Component
{
    public string $email = '';

    public string $role = '';

    public string $locale = 'es';

    public function mount(): void
    {
        $this->authorize('create', UserInvitation::class);
        $this->locale = app()->getLocale();
    }

    public function save(IssueUserInvitation $issue): void
    {
        $this->authorize('create', UserInvitation::class);
        $validated = $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['nullable', Rule::in(Role::assignableValues())],
            'locale' => ['required', Rule::in(['es', 'en'])],
        ]);

        if (! $issue->create(auth()->user(), $validated['email'], $validated['role'] ?: null, $validated['locale'])) {
            $this->addError('email', __('The invitation was saved, but the email could not be sent. Reissue it from the invitation list.'));

            return;
        }

        Flux::toast(variant: 'success', text: __('Invitation sent.'));
        $this->redirect(route('admin.users.invitations'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.users.create')->layout('layouts.app', [
            'breadcrumbs' => [
                ['label' => __('Users'), 'href' => route('admin.users.index')],
                ['label' => __('Invite user')],
            ],
        ]);
    }
}
