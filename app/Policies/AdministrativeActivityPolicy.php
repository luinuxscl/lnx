<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\AdministrativeActivity;
use App\Models\User;

class AdministrativeActivityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Root);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AdministrativeActivity $administrativeActivity): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AdministrativeActivity $administrativeActivity): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AdministrativeActivity $administrativeActivity): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AdministrativeActivity $administrativeActivity): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AdministrativeActivity $administrativeActivity): bool
    {
        return false;
    }
}
