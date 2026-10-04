<?php

namespace App\Ai;

use App\Ai\Agents\ChatAgent;
use App\Models\AiChatMessage;
use App\Models\AiConversation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Throwable;

/**
 * Service managing private chat conversations, history, streaming, and tool execution.
 */
class ChatService
{
    public function __construct(
        private readonly AiGateway $gateway,
        private readonly AiErrorCategorizer $categorizer,
    ) {}

    /**
     * Whether AI is configured and ready for chat.
     */
    public function isReady(): bool
    {
        return $this->gateway->isReady();
    }

    /**
     * Get all conversations belonging strictly to a user.
     *
     * @return Collection<int, AiConversation>
     */
    public function getConversations(User $user): Collection
    {
        return AiConversation::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Get a specific conversation belonging to a user.
     *
     * @throws AuthorizationException
     */
    public function getConversation(User $user, string $id): AiConversation
    {
        /** @var AiConversation $conversation */
        $conversation = AiConversation::query()
            ->with(['messages' => fn ($query) => $query->orderBy('created_at', 'asc')])
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        return $conversation;
    }

    /**
     * Create a new conversation for a user.
     */
    public function createConversation(User $user, ?string $title = null, ?string $pageContext = null): AiConversation
    {
        $sanitizedContext = $pageContext !== null ? $this->sanitizeContext($pageContext) : null;

        return AiConversation::create([
            'user_id' => $user->id,
            'title' => $title ?? __('New conversation'),
            'system_context' => $sanitizedContext,
        ]);
    }

    /**
     * Delete a conversation belonging to a user.
     *
     * @throws AuthorizationException
     */
    public function deleteConversation(User $user, string $id): void
    {
        $conversation = $this->getConversation($user, $id);
        $conversation->delete();
    }

    /**
     * Send a user message and process the response (supporting streaming or synchronous execution).
     *
     * @param  list<Tool>  $tools
     * @return array{user_message: AiChatMessage, assistant_message: AiChatMessage, text: string, status: string, error_category: string|null}
     */
    public function sendMessage(
        User $user,
        AiConversation $conversation,
        string $userContent,
        ?string $pageContext = null,
        array $tools = []
    ): array {
        if ((int) $conversation->user_id !== (int) $user->id) {
            throw new AuthorizationException(__('You do not own this conversation.'));
        }

        $userContent = trim($userContent);
        if ($userContent === '') {
            throw new \InvalidArgumentException(__('Message content cannot be empty.'));
        }

        // 1. Create User Message
        $userMessage = AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userContent,
            'status' => 'completed',
        ]);

        // Auto-update conversation title if default
        if ($conversation->title === __('New conversation') || empty($conversation->title)) {
            $conversation->update([
                'title' => Str::limit($userContent, 40),
            ]);
        }

        // 2. Create Pending Assistant Message
        $assistantMessage = AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => '',
            'status' => 'pending',
        ]);

        if (! $this->isReady()) {
            $assistantMessage->update([
                'content' => __('AI features are not configured or available.'),
                'status' => 'failed',
            ]);

            return [
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
                'text' => $assistantMessage->content,
                'status' => 'failed',
                'error_category' => AiErrorCategorizer::NOT_CONFIGURED,
            ];
        }

        // 3. Build Conversation History Context
        $maxContext = (int) config('lnx_ai.chat.max_context_messages', 20);

        $previousMessages = AiChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('id', '!=', $userMessage->id)
            ->where('id', '!=', $assistantMessage->id)
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->limit($maxContext)
            ->get()
            ->reverse();

        $history = [];
        foreach ($previousMessages as $msg) {
            if (in_array($msg->role, ['user', 'assistant', 'system'], true)) {
                $history[] = new Message($msg->role, $msg->content);
            }
        }

        // Include optional page context if provided
        $prompt = $userContent;
        if (! empty($pageContext)) {
            $prompt .= "\n\n<page_context>\n".$this->sanitizeContext($pageContext)."\n</page_context>";
        } elseif (! empty($conversation->system_context)) {
            $prompt .= "\n\n<page_context>\n".$conversation->system_context."\n</page_context>";
        }

        try {
            $agent = (new ChatAgent)
                ->withHistory($history)
                ->withTools($tools);

            $streamable = $this->gateway->stream(
                $agent,
                $prompt,
                application: 'chat',
                timeout: $agent->timeout(),
            );

            // Iterate chunks to compile full response text
            $responseText = '';
            foreach ($streamable as $event) {
                if (property_exists($event, 'delta') && is_string($event->delta)) {
                    $responseText .= $event->delta;
                } elseif ($event instanceof \Stringable) {
                    $responseText .= $event->__toString();
                }
            }

            if (empty($responseText) && ! empty($streamable->text)) {
                $responseText = $streamable->text;
            }

            $assistantMessage->update([
                'content' => trim($responseText),
                'status' => 'completed',
                'tokens' => $streamable->usage?->totalTokens(),
            ]);

            $conversation->touch();

            return [
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
                'text' => $assistantMessage->content,
                'status' => 'completed',
                'error_category' => null,
            ];
        } catch (Throwable $exception) {
            $category = $this->categorizer->categorize($exception);

            $assistantMessage->update([
                'content' => __('An error occurred while generating the response.'),
                'status' => 'failed',
            ]);

            return [
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
                'text' => $assistantMessage->content,
                'status' => 'failed',
                'error_category' => $category,
            ];
        }
    }

    /**
     * Sanitize page context to prevent sending sensitive form inputs or raw secrets.
     */
    protected function sanitizeContext(string $context): string
    {
        $context = strip_tags($context);
        // Truncate to reasonable length (e.g. max 1000 chars)
        $context = Str::limit(trim($context), 1000);

        return $context;
    }
}
