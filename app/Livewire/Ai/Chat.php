<?php

namespace App\Livewire\Ai;

use App\Ai\AiConfig;
use App\Ai\ChatService;
use App\Enums\Role;
use App\Models\AiAssistant;
use App\Models\AiConversation;
use App\Models\KnowledgeBase;
use App\Models\User;
use Flux;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Chat extends Component
{
    public ?string $activeConversationId = null;

    public string $inputPrompt = '';

    public ?string $pageContext = null;

    public bool $isProcessing = false;

    public ?string $errorMessage = null;

    /**
     * Knowledge bases attached to the active conversation.
     *
     * @var array<int, int>
     */
    public array $selectedBaseIds = [];

    public ?int $selectedAssistantId = null;

    public string $systemPrompt = '';

    public string $model = '';

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

        $this->hydrateConversationContext();
    }

    public function newConversation(ChatService $chatService): void
    {
        /** @var User $user */
        $user = auth()->user();

        $conv = $chatService->createConversation(
            $user,
            __('New conversation'),
            $this->pageContext,
            $this->selectedAssistantId,
            $this->selectedBaseIds,
        );

        $this->activeConversationId = $conv->id;
        $this->inputPrompt = '';
        $this->errorMessage = null;
        $this->hydrateConversationContext();
    }

    public function selectConversation(string $id, ChatService $chatService): void
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            $conv = $chatService->getConversation($user, $id);
            $this->activeConversationId = $conv->id;
            $this->errorMessage = null;
            $this->hydrateConversationContext();
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
                $this->hydrateConversationContext();
            }
            $this->errorMessage = null;
        } catch (\Throwable) {
            $this->errorMessage = __('Failed to delete conversation.');
        }
    }

    /**
     * Replace the knowledge bases attached to the active conversation.
     */
    public function updateKnowledgeBases(ChatService $chatService): void
    {
        if ($this->activeConversationId === null) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();

        try {
            $conv = $chatService->getConversation($user, $this->activeConversationId);
            $chatService->syncKnowledgeBases($user, $conv, $this->selectedBaseIds);
            $this->hydrateConversationContext();

            Flux::toast(variant: 'success', text: __('Knowledge bases updated for this chat.'));
        } catch (\Throwable) {
            $this->errorMessage = __('Failed to update the knowledge bases.');
        }
    }

    /**
     * Attach (or clear) the assistant preset of the active conversation.
     */
    public function updateAssistant(ChatService $chatService): void
    {
        if ($this->activeConversationId === null) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();

        try {
            $conv = $chatService->getConversation($user, $this->activeConversationId);

            $assistant = $this->selectedAssistantId !== null
                ? AiAssistant::query()->visibleTo($user)->find($this->selectedAssistantId)
                : null;

            $conv->update(['assistant_id' => $assistant?->id]);
            $this->hydrateConversationContext();

            Flux::toast(variant: 'success', text: __('Assistant updated.'));
        } catch (\Throwable) {
            $this->errorMessage = __('Failed to update the assistant.');
        }
    }

    /**
     * Update the system prompt and model of the active conversation.
     *
     * Only root and admin may set these per conversation.
     */
    public function updateConversationConfig(ChatService $chatService): void
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->hasRole([Role::Root, Role::Admin])) {
            $this->errorMessage = __('You are not allowed to change the prompt or model.');

            return;
        }

        if ($this->activeConversationId === null) {
            return;
        }

        $this->validate([
            'systemPrompt' => ['nullable', 'string', 'max:4000'],
            'model' => ['nullable', 'string', 'max:255'],
        ]);

        $model = trim($this->model);

        if ($model !== '' && ! app(AiConfig::class)->isModelAllowed($model)) {
            $this->addError('model', __('The selected model is not allowed.'));

            return;
        }

        try {
            $conv = $chatService->getConversation($user, $this->activeConversationId);
            $conv->update([
                'system_prompt' => trim($this->systemPrompt) !== '' ? trim($this->systemPrompt) : null,
                'model' => $model !== '' ? $model : null,
            ]);

            Flux::toast(variant: 'success', text: __('Conversation configuration updated.'));
        } catch (\Throwable) {
            $this->errorMessage = __('Failed to update the conversation configuration.');
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
            $conv = $chatService->createConversation(
                $user,
                null,
                $this->pageContext,
                $this->selectedAssistantId,
                $this->selectedBaseIds,
            );
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
            'bases' => KnowledgeBase::query()->visibleTo($user)->orderBy('name')->get(),
            'assistants' => AiAssistant::query()->visibleTo($user)->orderBy('name')->get(),
            'canConfigure' => $user->hasRole([Role::Root, Role::Admin]),
            'availableModels' => array_keys(app(AiConfig::class)->allowedModels()),
        ])->layout('layouts.app');
    }

    /**
     * Load the active conversation configuration into the form state.
     */
    protected function hydrateConversationContext(): void
    {
        $this->selectedBaseIds = [];
        $this->selectedAssistantId = null;
        $this->systemPrompt = '';
        $this->model = '';

        if ($this->activeConversationId === null) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();

        try {
            $conv = app(ChatService::class)->getConversation($user, $this->activeConversationId);
        } catch (\Throwable) {
            $this->activeConversationId = null;

            return;
        }

        $baseIds = $conv->knowledgeBases
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        // Reflect the bases inherited from the assistant preset when none are explicit.
        if ($baseIds === [] && $conv->assistant !== null) {
            $baseIds = $conv->assistant->knowledgeBases
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        $this->selectedBaseIds = $baseIds;
        $this->selectedAssistantId = $conv->assistant_id;
        $this->systemPrompt = (string) $conv->system_prompt;
        $this->model = (string) $conv->model;
    }
}
