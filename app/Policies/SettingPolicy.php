<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    /**
     * Determine whether the user can view the settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Root);
    }

    /**
     * Determine whether the user can update the settings.
     */
    public function update(User $user, Setting $setting): bool
    {
        return $user->hasRole(Role::Root);
    }
}
