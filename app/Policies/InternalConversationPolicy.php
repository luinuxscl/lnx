<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\InternalConversation;
use App\Models\InternalConversationParticipant;
use App\Models\User;

class InternalConversationPolicy
{
    /**
     * Determine whether the user can view the conversation.
     */
    public function view(User $user, InternalConversation $conversation): bool
    {
        if ($user->isSuspended()) {
            return false;
        }

        if ($conversation->isSupport()) {
            return $user->hasRole(Role::Root) || $conversation->hasParticipant($user);
        }

        return $conversation->hasParticipant($user);
    }

    /**
     * Determine whether the user can send messages in the conversation.
     */
    public function sendMessage(User $user, InternalConversation $conversation): bool
    {
        if ($user->isSuspended()) {
            return false;
        }

        if (! $this->view($user, $conversation)) {
            return false;
        }

        if ($conversation->isAnnouncement()) {
            $participant = $conversation->participantFor($user);

            return $participant && $participant->role === InternalConversationParticipant::ROLE_CREATOR;
        }

        if ($conversation->isSupport()) {
            if ($conversation->ticket && $conversation->ticket->status === 'closed' && ! $user->hasRole(Role::Root)) {
                return false;
            }

            return true;
        }

        return $conversation->hasParticipant($user);
    }

    /**
     * Determine whether the user can create a direct conversation.
     */
    public function createDirect(User $user): bool
    {
        return ! $user->isSuspended();
    }

    /**
     * Determine whether the user can create a group conversation.
     */
    public function createGroup(User $user): bool
    {
        return ! $user->isSuspended() && ($user->hasRole(Role::Root) || $user->hasRole(Role::Admin));
    }

    /**
     * Determine whether the user can broadcast an announcement.
     */
    public function createAnnouncement(User $user): bool
    {
        return ! $user->isSuspended() && ($user->hasRole(Role::Root) || $user->hasRole(Role::Admin));
    }
}
