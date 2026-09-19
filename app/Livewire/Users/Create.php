<?php

namespace App\Livewire\Users;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create user')]
class Create extends Component
{
    use PasswordValidationRules, ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = '';

    /**
     * Create a new user.
     */
    public function save(): void
    {
        $this->authorize('create', User::class);

        $validated = $this->validate([
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'role' => $this->roleRules(),
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        if ($validated['role'] !== '') {
            $user->assignRole($validated['role']);
        }

        Flux::toast(variant: 'success', text: __('User created.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    /**
     * Get the validation rules for the role field.
     *
     * @return array<int, mixed>
     */
    protected function roleRules(): array
    {
        return ['nullable', 'string', Rule::in(Role::assignableValues())];
    }

    public function render(): View
    {
        return view('livewire.users.create')
            ->layout('layouts.app', [
                'breadcrumbs' => [
                    ['label' => __('Users'), 'href' => route('admin.users.index')],
                    ['label' => __('Create user')],
                ],
            ]);
    }
}
