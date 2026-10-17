<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\OutboundEmail;
use App\Models\User;

class OutboundEmailPolicy
{
    /**
     * Determine whether the user can view the outbound email log.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Root);
    }

    /**
     * Determine whether the user can view a single outbound email record.
     */
    public function view(User $user, OutboundEmail $email): bool
    {
        return $user->hasRole(Role::Root);
    }
}
