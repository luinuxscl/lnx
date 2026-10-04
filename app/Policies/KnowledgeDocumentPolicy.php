<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\KnowledgeDocument;
use App\Models\User;

class KnowledgeDocumentPolicy
{
    /**
     * Determine whether the user can view any knowledge documents.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the knowledge document.
     */
    public function view(User $user, KnowledgeDocument $document): bool
    {
        if ($document->withdrawn_at !== null) {
            return false;
        }

        if ($user->hasRole([Role::Root, Role::Admin])) {
            return true;
        }

        return $document->user_id === null || (int) $user->id === (int) $document->user_id;
    }

    /**
     * Determine whether the user can create knowledge documents.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the knowledge document.
     */
    public function update(User $user, KnowledgeDocument $document): bool
    {
        if ($user->hasRole([Role::Root, Role::Admin])) {
            return true;
        }

        return (int) $user->id === (int) $document->user_id;
    }

    /**
     * Determine whether the user can delete the knowledge document.
     */
    public function delete(User $user, KnowledgeDocument $document): bool
    {
        if ($user->hasRole([Role::Root, Role::Admin])) {
            return true;
        }

        return (int) $user->id === (int) $document->user_id;
    }

    /**
     * Determine whether the user can reindex the knowledge document.
     */
    public function reindex(User $user, KnowledgeDocument $document): bool
    {
        if ($user->hasRole([Role::Root, Role::Admin])) {
            return true;
        }

        return (int) $user->id === (int) $document->user_id;
    }

    /**
     * Determine whether the user can withdraw the knowledge document.
     */
    public function withdraw(User $user, KnowledgeDocument $document): bool
    {
        if ($user->hasRole([Role::Root, Role::Admin])) {
            return true;
        }

        return (int) $user->id === (int) $document->user_id;
    }
}
