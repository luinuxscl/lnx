<?php

namespace App\Ai\Agents;

use App\Ai\LnxAgent;
use App\Ai\Tools\ProductHelpSearchTool;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;

/**
 * Agent for the interactive chat feature.
 */
class ChatAgent extends LnxAgent implements Conversational, HasTools
{
    /**
     * @var list<Message>
     */
    protected array $historyMessages = [];

    /**
     * @var list<Tool>
     */
    protected array $customTools = [];

    /**
     * Set explicit history messages for conversational context.
     *
     * @param  list<Message>  $messages
     */
    public function withHistory(array $messages): static
    {
        $this->historyMessages = $messages;

        return $this;
    }

    public function instructions(): string
    {
        return <<<'PROMPT'
        You are an intelligent, helpful interactive assistant for the lnx application.
        Provide concise, accurate, helpful, and safe answers to the user's questions.
        Always answer in the language used by the user (Spanish or English).
        Do not make up facts, execute code, or reveal sensitive credentials or raw database contents.
        When asked about product features or settings, use the available tools or system documentation context.
        PROMPT;
    }

    public function messages(): iterable
    {
        return $this->historyMessages;
    }

    public function tools(): iterable
    {
        return $this->customTools !== [] ? $this->customTools : [new ProductHelpSearchTool];
    }

    public function maxTokens(): int
    {
        return max(1, (int) config('lnx_ai.chat.max_output_tokens', 2048));
    }

    public function timeout(): int
    {
        return max(1, (int) config('lnx_ai.chat.timeout', 60));
    }
}
