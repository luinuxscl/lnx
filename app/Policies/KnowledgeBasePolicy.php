<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\KnowledgeBase;
use App\Models\User;

class KnowledgeBasePolicy
{
    /**
     * Determine whether the user can browse knowledge bases.
     *
     * The listing is always filtered by audience, so any authenticated user
     * may open the screen.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the knowledge base.
     */
    public function view(User $user, KnowledgeBase $base): bool
    {
        return $base->isVisibleTo($user);
    }

    /**
     * Determine whether the user can manage (create, edit, delete) bases.
     */
    public function manageAny(User $user): bool
    {
        return $user->hasRole([Role::Root, Role::Admin]);
    }

    /**
     * Determine whether the user can create a knowledge base.
     */
    public function create(User $user): bool
    {
        return $this->manageAny($user);
    }

    /**
     * Determine whether the user can update the knowledge base.
     */
    public function update(User $user, KnowledgeBase $base): bool
    {
        return $this->manageAny($user) && $base->audience->allows($user);
    }

    /**
     * Determine whether the user can delete the knowledge base.
     */
    public function delete(User $user, KnowledgeBase $base): bool
    {
        return $this->manageAny($user) && $base->audience->allows($user);
    }
}
