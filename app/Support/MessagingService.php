<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\InternalConversation;
use App\Models\InternalConversationParticipant;
use App\Models\InternalMessage;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\NewInternalMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MessagingService
{
    public function __construct(
        protected TicketService $ticketService,
    ) {}

    public function createDirectConversation(User $actor, User $recipient): InternalConversation
    {
        if ($actor->id === $recipient->id) {
            throw ValidationException::withMessages([
                'recipient' => __('You cannot start a conversation with yourself.'),
            ]);
        }

        if ($actor->isSuspended() || $recipient->isSuspended()) {
            throw ValidationException::withMessages([
                'recipient' => __('Cannot start a conversation with a suspended user.'),
            ]);
        }

        // Check if a direct conversation already exists between these two users
        $existing = InternalConversation::query()
            ->where('type', InternalConversation::TYPE_DIRECT)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $actor->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $recipient->id))
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($actor, $recipient): InternalConversation {
            $conversation = InternalConversation::create([
                'type' => InternalConversation::TYPE_DIRECT,
                'created_by' => $actor->id,
                'last_message_at' => now(),
            ]);

            $conversation->participants()->create([
                'user_id' => $actor->id,
                'role' => InternalConversationParticipant::ROLE_CREATOR,
                'last_read_at' => now(),
            ]);

            $conversation->participants()->create([
                'user_id' => $recipient->id,
                'role' => InternalConversationParticipant::ROLE_MEMBER,
                'last_read_at' => null,
            ]);

            return $conversation;
        });
    }

    /**
     * @param  list<int>  $participantUserIds
     */
    public function createGroupConversation(User $actor, string $title, array $participantUserIds): InternalConversation
    {
        abort_unless($actor->hasRole(Role::Root) || $actor->hasRole(Role::Admin), 403);
        abort_if($actor->isSuspended(), 403);

        $cleanTitle = trim($title);
        if (blank($cleanTitle)) {
            throw ValidationException::withMessages([
                'title' => __('Group title is required.'),
            ]);
        }

        $userIds = array_values(array_unique(array_filter($participantUserIds, fn ($id) => $id !== $actor->id)));
        $validUsers = User::whereIn('id', $userIds)->whereNull('suspended_at')->get();

        return DB::transaction(function () use ($actor, $cleanTitle, $validUsers): InternalConversation {
            $conversation = InternalConversation::create([
                'type' => InternalConversation::TYPE_GROUP,
                'title' => $cleanTitle,
                'created_by' => $actor->id,
                'last_message_at' => now(),
            ]);

            $conversation->participants()->create([
                'user_id' => $actor->id,
                'role' => InternalConversationParticipant::ROLE_CREATOR,
                'last_read_at' => now(),
            ]);

            foreach ($validUsers as $user) {
                $conversation->participants()->create([
                    'user_id' => $user->id,
                    'role' => InternalConversationParticipant::ROLE_MEMBER,
                    'last_read_at' => null,
                ]);
            }

            return $conversation;
        });
    }

    /**
     * @param  list<int>  $recipientUserIds
     */
    public function createAnnouncement(User $actor, string $title, string $body, array $recipientUserIds, string $requestKey): InternalConversation
    {
        abort_unless($actor->hasRole(Role::Root) || $actor->hasRole(Role::Admin), 403);
        abort_if($actor->isSuspended(), 403);

        $cleanTitle = trim($title);
        $cleanBody = trim($body);

        if (blank($cleanTitle)) {
            throw ValidationException::withMessages(['title' => __('Announcement title is required.')]);
        }
        if (blank($cleanBody)) {
            throw ValidationException::withMessages(['body' => __('Announcement body is required.')]);
        }

        $userIds = array_values(array_unique(array_filter($recipientUserIds, fn ($id) => $id !== $actor->id)));
        $validUsers = User::whereIn('id', $userIds)->whereNull('suspended_at')->get();

        if ($validUsers->isEmpty()) {
            throw ValidationException::withMessages(['recipients' => __('At least one valid recipient is required.')]);
        }

        return DB::transaction(function () use ($actor, $cleanTitle, $cleanBody, $validUsers, $requestKey): InternalConversation {
            $conversation = InternalConversation::create([
                'type' => InternalConversation::TYPE_ANNOUNCEMENT,
                'title' => $cleanTitle,
                'created_by' => $actor->id,
                'last_message_at' => now(),
            ]);

            $conversation->participants()->create([
                'user_id' => $actor->id,
                'role' => InternalConversationParticipant::ROLE_CREATOR,
                'last_read_at' => now(),
            ]);

            foreach ($validUsers as $user) {
                $conversation->participants()->create([
                    'user_id' => $user->id,
                    'role' => InternalConversationParticipant::ROLE_RECIPIENT,
                    'last_read_at' => null,
                ]);
            }

            $message = $conversation->messages()->create([
                'user_id' => $actor->id,
                'sender_type' => InternalMessage::SENDER_USER,
                'sender_name' => $actor->name,
                'body' => $cleanBody,
                'request_key' => $requestKey,
            ]);

            $this->notifyParticipants($conversation, $message, $actor);

            return $conversation;
        });
    }

    public function sendMessage(
        User $actor,
        InternalConversation $conversation,
        string $body,
        string $requestKey,
        string $senderType = InternalMessage::SENDER_USER,
        ?string $senderName = null,
        ?User $onBehalfOf = null,
    ): InternalMessage {
        abort_if($actor->isSuspended(), 403);

        $cleanBody = trim($body);
        if (blank($cleanBody)) {
            throw ValidationException::withMessages(['body' => __('Message cannot be empty.')]);
        }

        // Enforce user sender_type for regular sends
        if ($senderType !== InternalMessage::SENDER_USER && ! $actor->hasRole(Role::Root)) {
            $senderType = InternalMessage::SENDER_USER;
        }

        // Authorize viewing and sending
        $this->authorizeAccess($actor, $conversation);
        abort_unless($actor->can('sendMessage', $conversation), 403);

        // Check idempotency
        if ($existing = InternalMessage::where('internal_conversation_id', $conversation->id)->where('request_key', $requestKey)->first()) {
            return $existing;
        }

        // Support conversation: delegate to TicketService to ensure single official support history
        if ($conversation->isSupport()) {
            $ticket = $conversation->ticket;
            if (! $ticket) {
                throw (new ModelNotFoundException)->setModel(SupportTicket::class);
            }

            $supportMessage = $this->ticketService->reply($actor, $ticket, $cleanBody, internal: false, image: null, requestKey: $requestKey);

            return $this->syncSupportReply($ticket, $supportMessage)
                ?? throw new \RuntimeException('Failed to sync support reply into messaging.');
        }

        return DB::transaction(function () use ($actor, $conversation, $cleanBody, $requestKey, $senderType, $senderName, $onBehalfOf): InternalMessage {
            $message = $conversation->messages()->create([
                'user_id' => $onBehalfOf ? $onBehalfOf->id : $actor->id,
                'sender_type' => $senderType,
                'sender_name' => $senderName ?? $actor->name,
                'body' => $cleanBody,
                'request_key' => $requestKey,
            ]);

            $conversation->update(['last_message_at' => now()]);

            $participant = $conversation->participantFor($actor);
            if ($participant) {
                $participant->update(['last_read_at' => now()]);
            }

            $this->notifyParticipants($conversation, $message, $actor);

            return $message;
        });
    }

    public function sendSystemMessage(
        InternalConversation $conversation,
        string $body,
        string $requestKey,
        ?string $senderName = null,
    ): InternalMessage {
        if ($existing = InternalMessage::where('internal_conversation_id', $conversation->id)->where('request_key', $requestKey)->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($conversation, $body, $requestKey, $senderName): InternalMessage {
            $message = $conversation->messages()->create([
                'user_id' => null,
                'sender_type' => InternalMessage::SENDER_SYSTEM,
                'sender_name' => $senderName ?? __('System'),
                'body' => $body,
                'request_key' => $requestKey,
            ]);

            $conversation->update(['last_message_at' => now()]);
            $this->notifyParticipants($conversation, $message, null);

            return $message;
        });
    }

    public function sendAiMessage(
        InternalConversation $conversation,
        string $body,
        string $requestKey,
        ?User $responsibleUser = null,
        ?string $agentName = null,
    ): InternalMessage {
        if ($existing = InternalMessage::where('internal_conversation_id', $conversation->id)->where('request_key', $requestKey)->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($conversation, $body, $requestKey, $responsibleUser, $agentName): InternalMessage {
            $message = $conversation->messages()->create([
                'user_id' => $responsibleUser?->id,
                'sender_type' => InternalMessage::SENDER_AI,
                'sender_name' => $agentName ?? __('AI Assistant'),
                'body' => $body,
                'request_key' => $requestKey,
            ]);

            $conversation->update(['last_message_at' => now()]);
            $this->notifyParticipants($conversation, $message, $responsibleUser);

            return $message;
        });
    }

    public function markAsRead(User $user, InternalConversation $conversation): void
    {
        $participant = $conversation->participantFor($user);
        if ($participant) {
            $participant->update(['last_read_at' => now()]);
        }
    }

    public function ensureSupportConversation(SupportTicket $ticket): InternalConversation
    {
        $existing = InternalConversation::where('support_ticket_id', $ticket->id)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($ticket): InternalConversation {
            $conversation = InternalConversation::create([
                'type' => InternalConversation::TYPE_SUPPORT,
                'title' => $ticket->title,
                'created_by' => $ticket->user_id,
                'support_ticket_id' => $ticket->id,
                'last_message_at' => $ticket->created_at,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,
            ]);

            $conversation->participants()->create([
                'user_id' => $ticket->user_id,
                'role' => InternalConversationParticipant::ROLE_CREATOR,
                'last_read_at' => $ticket->created_at,
            ]);

            // Sync initial description as the first message
            $conversation->messages()->create([
                'user_id' => $ticket->user_id,
                'sender_type' => InternalMessage::SENDER_USER,
                'sender_name' => $ticket->user->name,
                'body' => $ticket->description,
                'request_key' => 'ticket-initial-'.$ticket->id,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->created_at,
            ]);

            // Sync any existing public replies
            foreach ($ticket->messages()->where('internal', false)->orderBy('id')->get() as $reply) {
                $conversation->messages()->create([
                    'user_id' => $reply->user_id,
                    'sender_type' => InternalMessage::SENDER_USER,
                    'sender_name' => $reply->user->name,
                    'body' => $reply->body,
                    'request_key' => $reply->request_key,
                    'support_message_id' => $reply->id,
                    'created_at' => $reply->created_at,
                    'updated_at' => $reply->updated_at,
                ]);
            }

            return $conversation;
        });
    }

    public function syncSupportReply(SupportTicket $ticket, SupportMessage $message): ?InternalMessage
    {
        // Internal notes are NEVER synced into the public conversation
        if ($message->internal) {
            return null;
        }

        $conversation = $this->ensureSupportConversation($ticket);

        if ($existing = InternalMessage::where('internal_conversation_id', $conversation->id)->where('support_message_id', $message->id)->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($conversation, $message): InternalMessage {
            $internalMessage = $conversation->messages()->create([
                'user_id' => $message->user_id,
                'sender_type' => InternalMessage::SENDER_USER,
                'sender_name' => $message->user->name,
                'body' => $message->body,
                'request_key' => $message->request_key,
                'support_message_id' => $message->id,
                'created_at' => $message->created_at,
                'updated_at' => $message->updated_at,
            ]);

            $conversation->update(['last_message_at' => $message->created_at]);

            return $internalMessage;
        });
    }

    public function syncSupportStatusChange(SupportTicket $ticket, string $previousStatus, string $newStatus): ?InternalMessage
    {
        $conversation = $this->ensureSupportConversation($ticket);
        $requestKey = 'status-change-'.$ticket->id.'-'.$previousStatus.'-'.$newStatus.'-'.$ticket->updated_at->timestamp;

        $body = __('Support Ticket status changed from :previous to :new.', [
            'previous' => $previousStatus,
            'new' => $newStatus,
        ]);

        if (! blank($ticket->resolution)) {
            $body .= ' '.__('Resolution: :res', ['res' => $ticket->resolution]);
        }

        return $this->sendSystemMessage($conversation, $body, $requestKey);
    }

    public function authorizeAccess(User $actor, InternalConversation $conversation): void
    {
        if ($actor->isSuspended()) {
            throw (new ModelNotFoundException)->setModel(InternalConversation::class, [$conversation->id]);
        }

        if ($conversation->isSupport()) {
            if (! $actor->hasRole(Role::Root) && ! $conversation->hasParticipant($actor)) {
                throw (new ModelNotFoundException)->setModel(InternalConversation::class, [$conversation->id]);
            }

            return;
        }

        if (! $conversation->hasParticipant($actor)) {
            throw (new ModelNotFoundException)->setModel(InternalConversation::class, [$conversation->id]);
        }
    }

    private function notifyParticipants(InternalConversation $conversation, InternalMessage $message, ?User $sender): void
    {
        // In support conversations, TicketService already dispatches notifications, so avoid duplicate notices
        if ($conversation->isSupport()) {
            return;
        }

        $bodyPreview = Str::limit($message->body, 120);
        $senderName = $message->displaySenderName();

        $query = $conversation->users();
        if ($sender) {
            $query->where('users.id', '!=', $sender->id);
        }

        foreach ($query->whereNull('suspended_at')->get() as $recipient) {
            $recipient->notify(new NewInternalMessage(
                conversationId: $conversation->id,
                messageId: $message->id,
                senderName: $senderName,
                type: $conversation->type,
                bodyPreview: $bodyPreview,
            ));
        }
    }
}
