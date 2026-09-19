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

#[Title('Edit user')]
class Edit extends Component
{
    use PasswordValidationRules, ProfileValidationRules;

    public User $user;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = '';

    /**
     * Mount the component.
     */
    public function mount(User $user): void
    {
        abort_if($user->hasRole(Role::Root), 403, 'Root accounts cannot be edited.');

        $this->authorize('update', $user);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;

        $primaryRole = $user->primaryRole();
        $this->role = $primaryRole instanceof Role ? $primaryRole->value : '';
    }

    /**
     * Update the given user.
     */
    public function save(): void
    {
        abort_if($this->user->hasRole(Role::Root), 403, 'Root accounts cannot be edited.');

        $this->authorize('update', $this->user);

        $rules = [
            ...$this->profileRules($this->user->id),
            'role' => $this->roleRules(),
        ];

        if ($this->password !== '') {
            $rules['password'] = $this->passwordRules();
        }

        $validated = $this->validate($rules);

        $this->user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($this->password !== '') {
            $this->user->password = $validated['password'];
        }

        $this->user->save();

        $this->user->syncRoles(
            $validated['role'] === '' ? [] : [$validated['role']],
        );

        Flux::toast(variant: 'success', text: __('User updated.'));

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
        return view('livewire.users.edit')
            ->layout('layouts.app', [
                'breadcrumbs' => [
                    ['label' => __('Users'), 'href' => route('admin.users.index')],
                    ['label' => __('Edit user')],
                ],
            ]);
    }
}
