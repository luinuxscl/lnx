<?php

namespace App\Policies;

use App\Models\AiConversation;
use App\Models\User;

class AiConversationPolicy
{
    /**
     * Determine whether the user can view any conversations.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the conversation.
     */
    public function view(User $user, AiConversation $conversation): bool
    {
        return (int) $user->id === (int) $conversation->user_id;
    }

    /**
     * Determine whether the user can create conversations.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the conversation.
     */
    public function update(User $user, AiConversation $conversation): bool
    {
        return (int) $user->id === (int) $conversation->user_id;
    }

    /**
     * Determine whether the user can delete the conversation.
     */
    public function delete(User $user, AiConversation $conversation): bool
    {
        return (int) $user->id === (int) $conversation->user_id;
    }
}
