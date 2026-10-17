<?php

namespace App\Livewire\Users;

use App\Actions\CreateVerifiedUser;
use App\Concerns\PasswordValidationRules;
use App\Enums\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class CreateDirect extends Component
{
    use PasswordValidationRules;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = '';

    public function mount(): void
    {
        $this->authorize('createDirectly', User::class);
    }

    public function save(CreateVerifiedUser $create): void
    {
        $this->authorize('createDirectly', User::class);

        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => $this->passwordRules(),
            'role' => ['nullable', Rule::in(Role::assignableValues())],
        ]);

        $create->handle(auth()->user(), $validated['name'], $validated['email'], $validated['password'], $validated['role'] ?: null);

        Flux::toast(variant: 'success', text: __('User created.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.users.create-direct');
    }
}
