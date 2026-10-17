<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the list of users.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::Root, Role::Admin]);
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([Role::Root, Role::Admin]);
    }

    /**
     * Determine whether the user can create an account directly, already verified.
     *
     * Only superusers may bypass the invitation flow and confirm the email.
     */
    public function createDirectly(User $user): bool
    {
        return $user->hasRole(Role::Root);
    }

    /**
     * Determine whether the user can update the given user.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasAnyRole([Role::Root, Role::Admin])
            && ! $model->hasRole(Role::Root);
    }

    /**
     * Determine whether the user can delete the given user.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->hasAnyRole([Role::Root, Role::Admin])
            && ! $model->hasRole(Role::Root)
            && ! $user->is($model);
    }

    public function suspend(User $user, User $model): bool
    {
        return $user->hasAnyRole([Role::Root, Role::Admin])
            && ! $model->hasRole(Role::Root)
            && ! $user->is($model);
    }

    public function reactivate(User $user, User $model): bool
    {
        return $user->hasAnyRole([Role::Root, Role::Admin])
            && ! $model->hasRole(Role::Root)
            && ! $user->is($model);
    }
}
