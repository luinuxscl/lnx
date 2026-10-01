<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserInvitation;

class UserInvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::Root, Role::Admin]);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, UserInvitation $invitation): bool
    {
        return $this->viewAny($user);
    }
}
