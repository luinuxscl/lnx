<?php

namespace App\Livewire\Users;

use App\Enums\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\View\View;
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

        $user->delete();

        Flux::toast(variant: 'success', text: __('User deleted.'));
    }

    public function render(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('roles')
            ->when(
                $this->search !== '',
                fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")),
            )
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.users.index', ['users' => $users])
            ->layout('layouts.app', [
                'breadcrumbs' => [
                    ['label' => __('Users')],
                ],
            ]);
    }
}
