<?php

namespace App\Livewire\Ai;

use App\Ai\AiErrorCategorizer;
use App\Ai\TextAssistant as TextAssistantService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Component;

/**
 * Reusable text assistant for forms.
 *
 * Drop it next to a field and bind it with `wire:model`. It only ever sends the
 * bound text plus the selected action and target language: it never reads other
 * form fields. Accepting writes the proposal back through the model binding, so
 * generation never saves anything on its own. Authorization is delegated to a
 * Gate ability the derived project defines and passes as `ability`.
 */
class TextAssistant extends Component
{
    /**
     * The text being edited. Two-way bound to the parent component.
     */
    #[Modelable]
    public string $text = '';

    /**
     * The selected action. One of {@see TextAssistantService::ACTIONS}.
     */
    public string $action = 'improve';

    /**
     * The target language for the translate action.
     */
    public ?string $language = null;

    /**
     * The Gate ability required to use the assistant.
     */
    #[Locked]
    public string $ability = 'use-text-assistant';

    /**
     * The generated proposal, or null when none is pending.
     */
    #[Locked]
    public ?string $proposal = null;

    /**
     * Metadata of the pending proposal.
     *
     * @var array<string, mixed>
     */
    #[Locked]
    public array $proposalMeta = [];

    /**
     * Monotonic token used to discard stale or superseded responses.
     */
    #[Locked]
    public int $requestId = 0;

    /**
     * Generate a proposal for the current text, action and language.
     */
    public function generate(TextAssistantService $assistant): void
    {
        $this->authorizeUse();
        $this->resetErrorBag('generation');

        $data = $this->validate($assistant->rules());

        $token = ++$this->requestId;
        $snapshot = $this->snapshot();

        $result = $assistant->run($data['text'], $data['action'], $data['language'] ?? null);

        if ($token !== $this->requestId || $snapshot !== $this->snapshot()) {
            return;
        }

        if ($result['status'] !== 'success' || $result['text'] === null || $result['text'] === '') {
            $message = $this->categoryMessage($result['category']);
            $this->addError('generation', $message);
            Flux::toast(variant: 'danger', text: $message);

            return;
        }

        $this->proposal = $result['text'];
        $this->proposalMeta = [
            'action' => $data['action'],
            'language' => $data['language'] ?? null,
        ];
    }

    /**
     * Apply the proposal to the bound text and clear it.
     */
    public function accept(): void
    {
        $this->authorizeUse();

        if ($this->proposal === null) {
            return;
        }

        $this->text = $this->proposal;
        $this->proposal = null;
        $this->proposalMeta = [];
    }

    /**
     * Discard the proposal, keeping the original text.
     */
    public function discard(): void
    {
        $this->authorizeUse();
        $this->invalidateProposal();
    }

    /**
     * Invalidate a pending proposal when the text changes.
     */
    public function updatedText(): void
    {
        $this->invalidateProposal();
    }

    /**
     * Invalidate a pending proposal when the action changes.
     */
    public function updatedAction(): void
    {
        $this->invalidateProposal();

        if ($this->action !== 'translate') {
            $this->language = null;
        }
    }

    /**
     * Invalidate a pending proposal when the language changes.
     */
    public function updatedLanguage(): void
    {
        $this->invalidateProposal();
    }

    public function render(): View
    {
        $this->authorizeUse();

        $assistant = app(TextAssistantService::class);

        return view('livewire.ai.text-assistant', [
            'actions' => $assistant->actions(),
            'languages' => $assistant->languages(),
            'ready' => $assistant->isReady(),
            'maxInputChars' => $assistant->maxInputChars(),
        ]);
    }

    /**
     * Abort when the current user is not allowed to use the assistant.
     */
    protected function authorizeUse(): void
    {
        abort_unless(auth()->check() && Gate::allows($this->ability), 403);
    }

    /**
     * Bump the request token and drop the pending proposal.
     */
    protected function invalidateProposal(): void
    {
        $this->requestId++;
        $this->proposal = null;
        $this->proposalMeta = [];
    }

    /**
     * A stable fingerprint of the inputs a proposal was generated from.
     */
    protected function snapshot(): string
    {
        return hash('sha256', $this->text."\0".$this->action."\0".($this->language ?? ''));
    }

    /**
     * Translate an error category into a user-facing message.
     */
    protected function categoryMessage(string $category): string
    {
        return match ($category) {
            AiErrorCategorizer::NOT_CONFIGURED => __('The assistant is not configured. Your text is unchanged.'),
            AiErrorCategorizer::LOCAL_QUOTA => __('You have reached the AI usage limit. Your text is unchanged.'),
            AiErrorCategorizer::AUTHENTICATION => __('The AI provider rejected the request. Your text is unchanged.'),
            AiErrorCategorizer::CREDITS => __('The AI provider has no credits or quota left. Your text is unchanged.'),
            AiErrorCategorizer::RATE_LIMITED => __('The AI provider is rate limiting requests. Try again later.'),
            AiErrorCategorizer::TIMEOUT => __('The AI request timed out. Your text is unchanged.'),
            AiErrorCategorizer::UNAVAILABLE => __('The AI provider is unavailable. Try again later.'),
            AiErrorCategorizer::MODEL => __('The configured AI model is not compatible. Your text is unchanged.'),
            default => __('The assistant could not process the text. Your text is unchanged.'),
        };
    }
}
