<?php

namespace App\Livewire\Ai;

use App\Ai\ChatService;
use App\Models\AiConversation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Chat extends Component
{
    public ?string $activeConversationId = null;

    public string $inputPrompt = '';

    public ?string $pageContext = null;

    public bool $isProcessing = false;

    public ?string $errorMessage = null;

    public function mount(?string $conversation = null): void
    {
        $chatService = app(ChatService::class);

        /** @var User $user */
        $user = auth()->user();

        if ($conversation !== null) {
            try {
                $conv = $chatService->getConversation($user, $conversation);
                $this->activeConversationId = $conv->id;
            } catch (\Throwable) {
                $this->activeConversationId = null;
            }
        }

        if ($this->activeConversationId === null) {
            $recent = $chatService->getConversations($user)->first();
            if ($recent instanceof AiConversation) {
                $this->activeConversationId = $recent->id;
            }
        }
    }

    public function newConversation(ChatService $chatService): void
    {
        /** @var User $user */
        $user = auth()->user();

        $conv = $chatService->createConversation($user, __('New conversation'), $this->pageContext);
        $this->activeConversationId = $conv->id;
        $this->inputPrompt = '';
        $this->errorMessage = null;
    }

    public function selectConversation(string $id, ChatService $chatService): void
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            $conv = $chatService->getConversation($user, $id);
            $this->activeConversationId = $conv->id;
            $this->errorMessage = null;
        } catch (\Throwable) {
            $this->errorMessage = __('Conversation not found or unauthorized.');
        }
    }

    public function deleteConversation(string $id, ChatService $chatService): void
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            $chatService->deleteConversation($user, $id);
            if ($this->activeConversationId === $id) {
                $this->activeConversationId = null;
                $recent = $chatService->getConversations($user)->first();
                if ($recent instanceof AiConversation) {
                    $this->activeConversationId = $recent->id;
                }
            }
            $this->errorMessage = null;
        } catch (\Throwable) {
            $this->errorMessage = __('Failed to delete conversation.');
        }
    }

    public function sendMessage(ChatService $chatService): void
    {
        /** @var User $user */
        $user = auth()->user();

        $prompt = trim($this->inputPrompt);
        if ($prompt === '') {
            return;
        }

        if ($this->activeConversationId === null) {
            $conv = $chatService->createConversation($user, null, $this->pageContext);
            $this->activeConversationId = $conv->id;
        } else {
            $conv = $chatService->getConversation($user, $this->activeConversationId);
        }

        $this->isProcessing = true;
        $this->errorMessage = null;
        $this->inputPrompt = '';

        try {
            $result = $chatService->sendMessage(
                $user,
                $conv,
                $prompt,
                $this->pageContext
            );

            if ($result['status'] === 'failed') {
                $this->errorMessage = match ($result['error_category']) {
                    'not_configured' => __('AI features are not configured or available.'),
                    'quota_exceeded' => __('AI usage quota has been exceeded.'),
                    default => __('An error occurred while generating the response.'),
                };
            }
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render(ChatService $chatService): View
    {
        /** @var User $user */
        $user = auth()->user();

        $conversations = $chatService->getConversations($user);

        $activeConversation = null;
        if ($this->activeConversationId !== null) {
            try {
                $activeConversation = $chatService->getConversation($user, $this->activeConversationId);
            } catch (\Throwable) {
                $this->activeConversationId = null;
            }
        }

        return view('livewire.ai.chat', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'isReady' => $chatService->isReady(),
        ])->layout('layouts.app');
    }
}
