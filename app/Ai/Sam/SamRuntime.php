<?php

namespace App\Ai\Sam;

use App\Actions\RecordMcpInvocation;
use App\Ai\AiGateway;
use App\Ai\Exceptions\AiNotConfiguredException;
use App\Ai\Exceptions\AiQuotaExceededException;
use App\Ai\Exceptions\ModelNotAllowedException;
use App\Ai\Exceptions\UnsupportedCapabilityException;
use App\Ai\Sam\Tools\SamProposeTool;
use App\Ai\Sam\Tools\SamReadTool;
use App\Ai\Sam\Tools\SamTool;
use App\Models\AiChatMessage;
use App\Models\AiConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Messages\Message;
use Throwable;

/**
 * Runtime of the platform agent.
 *
 * It owns the switch, the input bounds, the conversation, the quota (through
 * the kit's gateway), the persistence and the audit. It composes the
 * instructions from the registered profile and contributors, resolves only
 * read and proposal tools, and delegates confirmation in the owning module:
 * the runtime never writes on its own.
 */
class SamRuntime
{
    public const PROFILE = 'sam';

    public function __construct(
        private readonly AiGateway $gateway,
        private readonly SamRegistry $registry,
        private readonly SamAccess $access,
        private readonly SamInstructions $instructions,
    ) {}

    public function enabled(): bool
    {
        return $this->access->enabled();
    }

    public function ready(): bool
    {
        return $this->access->ready();
    }

    public function canUse(?User $actor): bool
    {
        return $this->access->canUse($actor);
    }

    public function permission(): string
    {
        return $this->access->permission();
    }

    public function application(): string
    {
        $application = config('lnx_sam.application', 'sam.turn');

        return is_string($application) && trim($application) !== '' ? trim($application) : 'sam.turn';
    }

    public function maxInputChars(): int
    {
        return max(1, (int) config('lnx_sam.max_input_chars', 2000));
    }

    public function maxContextMessages(): int
    {
        return max(1, (int) config('lnx_sam.max_context_messages', 20));
    }

    public function retentionDays(): int
    {
        return max(0, (int) config('lnx_sam.retention_days', 0));
    }

    public function titleChars(): int
    {
        return max(1, (int) config('lnx_sam.title_chars', SamTitle::MAX_CHARS));
    }

    /**
     * The component that mounts the global panel, when it must be shown.
     */
    public function surface(): ?string
    {
        $surface = $this->registry->surface();

        if ($surface === null || ! $this->enabled() || ! $this->canUse(auth()->user())) {
            return null;
        }

        return $surface;
    }

    /**
     * Threads of the actor, newest first, with their message count and last message.
     *
     * @return Collection<int, AiConversation>
     */
    public function conversations(User $actor): Collection
    {
        return AiConversation::query()
            ->where('user_id', $actor->getKey())
            ->where('profile', self::PROFILE)
            ->withCount('messages')
            ->with('latestMessage')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Most recent Sam conversation of an actor, without creating it.
     */
    public function findConversation(User $actor): ?AiConversation
    {
        return AiConversation::query()
            ->where('user_id', $actor->getKey())
            ->where('profile', self::PROFILE)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * One Sam conversation of the actor, or null when it is not theirs.
     */
    public function findForActor(User $actor, string $conversationId): ?AiConversation
    {
        $conversationId = trim($conversationId);

        if ($conversationId === '') {
            return null;
        }

        return AiConversation::query()
            ->whereKey($conversationId)
            ->where('user_id', $actor->getKey())
            ->where('profile', self::PROFILE)
            ->first();
    }

    /**
     * The conversation a turn lands on when the caller does not pick one,
     * created on first use.
     */
    public function conversationFor(User $actor): AiConversation
    {
        return DB::transaction(function () use ($actor): AiConversation {
            User::query()->whereKey($actor->getKey())->lockForUpdate()->first();

            return $this->findConversation($actor) ?? $this->startConversation($actor);
        });
    }

    /**
     * Open an empty thread. Its title is derived from the first message.
     */
    public function startConversation(User $actor): AiConversation
    {
        return AiConversation::create([
            'user_id' => $actor->getKey(),
            'title' => null,
            'profile' => self::PROFILE,
        ]);
    }

    /**
     * Messages of one conversation of the actor, oldest first.
     *
     * @return Collection<int, AiChatMessage>
     */
    public function messages(User $actor, ?AiConversation $conversation = null): Collection
    {
        $conversation ??= $this->findConversation($actor);

        if ($conversation === null || ! $this->owns($actor, $conversation)) {
            return new Collection;
        }

        return $conversation->messages()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Delete one conversation of the actor with its messages.
     *
     * A conversation of another identity is never deleted: the runtime answers
     * `false` and audits nothing, so a forged id cannot probe the store.
     */
    public function deleteConversation(User $actor, AiConversation|string $conversation): bool
    {
        if (! $this->canUse($actor)) {
            return false;
        }

        $conversation = $conversation instanceof AiConversation
            ? $this->findForActor($actor, (string) $conversation->getKey())
            : $this->findForActor($actor, $conversation);

        if ($conversation === null) {
            return false;
        }

        $counts = DB::transaction(function () use ($conversation): array {
            $messages = $conversation->messages()->count();
            $conversation->messages()->delete();
            $conversation->delete();

            return ['conversations' => 1, 'messages' => $messages];
        });

        $this->auditLifecycle($actor, 'delete_conversation', $counts);

        return true;
    }

    /**
     * Forget every Sam conversation of the actor.
     *
     * The audit log is kept: it records that the threads were deleted and how
     * many messages went with them, never their content.
     */
    public function forgetAll(User $actor): int
    {
        if (! $this->canUse($actor)) {
            return 0;
        }

        $counts = DB::transaction(function () use ($actor): array {
            $conversationIds = AiConversation::query()
                ->where('user_id', $actor->getKey())
                ->where('profile', self::PROFILE)
                ->select('id');

            $messages = AiChatMessage::query()
                ->whereIn('conversation_id', $conversationIds)
                ->count();

            AiChatMessage::query()
                ->whereIn('conversation_id', $conversationIds)
                ->delete();

            $conversations = AiConversation::query()
                ->where('user_id', $actor->getKey())
                ->where('profile', self::PROFILE)
                ->delete();

            return ['conversations' => $conversations, 'messages' => $messages];
        });

        if ($counts['conversations'] > 0) {
            $this->auditLifecycle($actor, 'forget_all', $counts);
        }

        return $counts['conversations'];
    }

    /**
     * Run one turn, on the selected conversation or on the actor's latest one.
     */
    public function send(
        User $actor,
        string $message,
        ?SamContext $context = null,
        ?AiConversation $conversation = null,
    ): SamReply {
        $message = trim($message);

        if ($message === '') {
            return SamReply::failed('empty_message');
        }

        if (mb_strlen($message) > $this->maxInputChars()) {
            return SamReply::failed('too_long');
        }

        if (! $this->canUse($actor)) {
            return SamReply::failed('forbidden');
        }

        if (! $this->enabled()) {
            return SamReply::failed('disabled');
        }

        if (! $this->ready()) {
            return SamReply::failed('not_configured');
        }

        // A phrase is the confirmation input, not a conversation turn: it is
        // never stored as a message.
        if (SamRedactor::isConfirmationPhrase($message)) {
            return SamReply::failed('confirmation_phrase');
        }

        if ($conversation !== null && ! $this->owns($actor, $conversation)) {
            return SamReply::failed('not_found');
        }

        $conversation ??= $this->conversationFor($actor);
        $correlationId = SamTurn::start((string) Str::uuid());

        $history = $this->contextMessages($conversation);
        $firstAssistantTurn = ! $conversation->messages()
            ->where('role', 'assistant')
            ->exists();

        AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => SamRedactor::redact($message),
            'status' => 'completed',
            'correlation_id' => $correlationId,
            'context' => $context?->sanitized(),
        ]);

        if (trim((string) $conversation->title) === '') {
            $title = SamTitle::from($message, $this->titleChars());

            if ($title !== null) {
                $conversation->forceFill(['title' => $title])->save();
            }
        }

        $tools = $this->toolsFor($actor);

        $agent = (new SamAgent)
            ->withHistory($history)
            ->withTools($tools)
            ->withInstructions($this->instructions->compose($actor, $message, $context, $firstAssistantTurn));

        try {
            $response = $this->gateway->prompt(
                $agent,
                $message,
                capabilities: $tools === [] ? [] : ['tools'],
                timeout: $agent->timeout(),
                application: $this->application(),
                reservedTokens: $agent->maxTokens(),
                correlationId: $correlationId,
            );
        } catch (Throwable $exception) {
            return SamReply::failed($this->errorKey($exception), $correlationId);
        }

        $text = trim((string) $response->text);

        if ($text === '') {
            return SamReply::failed('empty_response', $correlationId);
        }

        $text = $this->guaranteeDeclaration($text, $firstAssistantTurn);

        AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => SamRedactor::redact($text),
            'status' => 'completed',
            'tokens' => $response->usage->totalTokens(),
            'correlation_id' => $correlationId,
        ]);

        $conversation->touch();
        $this->prune($actor);

        return SamReply::ok($text, $correlationId, $response->usage->totalTokens());
    }

    /**
     * Read and proposal tools the actor may use in this turn.
     *
     * Only the kit bases are accepted: any other tool is rejected and audited,
     * so a module cannot expose a confirmation, deletion, merge or send tool.
     *
     * @return list<Tool>
     */
    public function toolsFor(User $actor): array
    {
        if (! $this->canUse($actor)) {
            return [];
        }

        $tools = [];
        $names = [];

        foreach ($this->registry->contributors() as $source => $contributor) {
            foreach ($contributor->readTools($actor) as $tool) {
                if ($tool instanceof SamReadTool) {
                    $this->addTool($tools, $names, $actor, $source, $tool);
                } else {
                    $this->rejectTool($actor, $source, $tool);
                }
            }

            foreach ($contributor->proposeTools($actor) as $tool) {
                if ($tool instanceof SamProposeTool) {
                    $this->addTool($tools, $names, $actor, $source, $tool);
                } else {
                    $this->rejectTool($actor, $source, $tool);
                }
            }
        }

        return $tools;
    }

    /**
     * Add a tool, rejecting a name already exposed by another module.
     *
     * Provider tool names are global in a turn: two modules cannot declare the
     * same one or the provider receives duplicate functions.
     *
     * @param  list<Tool>  $tools
     * @param  array<string, true>  $names
     */
    private function addTool(array &$tools, array &$names, User $actor, string $source, SamTool $tool): void
    {
        $name = $tool->name();

        if (isset($names[$name])) {
            $this->rejectTool($actor, $source, $tool, 'duplicate_name');

            return;
        }

        $names[$name] = true;
        $tools[] = $tool->withSource($source);
    }

    /**
     * Aggregate the screen context declared by the installed modules.
     */
    public function contextFor(Request $request): ?SamContext
    {
        foreach ($this->registry->contributors() as $contributor) {
            $context = $contributor->context($request);

            if ($context instanceof SamContext) {
                return $this->withSignals($contributor, auth()->user(), $context);
            }
        }

        return null;
    }

    /**
     * Screen context of one focused row, resolved by the module that owns it.
     *
     * The browser only names the resource and the id: the module re-checks the
     * actor's access, resolves the label and answers null when the record is
     * out of reach. Labels and filters from the client are never trusted.
     *
     * @param  array<string, mixed>  $filters
     */
    public function contextForRow(User $actor, string $resource, string $id, array $filters = []): ?SamContext
    {
        if (! $this->canUse($actor)) {
            return null;
        }

        $resource = trim($resource);
        $id = trim($id);

        if ($resource === '' || $id === '') {
            return null;
        }

        foreach ($this->registry->contributors() as $contributor) {
            $context = $contributor->rowContext($actor, $resource, $id, $filters);

            if ($context instanceof SamContext) {
                return $this->withSignals($contributor, $actor, $context);
            }
        }

        return null;
    }

    /**
     * Attach the deterministic signals the module declares for the context.
     *
     * They are computed by the module and only carried here: the runtime never
     * invents an observation.
     */
    private function withSignals(SamContributor $contributor, ?User $actor, SamContext $context): SamContext
    {
        if (! $actor instanceof User) {
            return $context;
        }

        $signals = $contributor->signals($actor, $context);

        if ($signals === []) {
            return $context;
        }

        return new SamContext(
            source: $context->source,
            resource: $context->resource,
            id: $context->id,
            label: $context->label,
            route: $context->route,
            filters: $context->filters,
            signals: $signals,
        );
    }

    /**
     * Conversation starters that respect the actor's permissions.
     *
     * @return list<string>
     */
    public function suggestions(User $actor, ?SamContext $context = null): array
    {
        if (! $this->canUse($actor)) {
            return [];
        }

        $suggestions = [];

        foreach ($this->registry->contributors() as $contributor) {
            foreach ($contributor->suggestions($actor, $context) as $suggestion) {
                if (trim($suggestion) !== '') {
                    $suggestions[] = trim($suggestion);
                }
            }
        }

        return array_values(array_unique(array_slice($suggestions, 0, 6)));
    }

    /**
     * Drafts of every module with the given correlation, visible to the actor.
     *
     * @return list<SamProposal>
     */
    public function proposals(User $actor, string $correlationId): array
    {
        if (! $this->canUse($actor)) {
            return [];
        }

        $proposals = [];

        foreach ($this->registry->contributors() as $contributor) {
            $presenter = $contributor->proposals();

            if ($presenter === null) {
                continue;
            }

            foreach ($presenter->forCorrelation($correlationId, $actor) as $proposal) {
                $proposals[] = $proposal;
            }
        }

        return $proposals;
    }

    /**
     * Confirm a draft through its owning module.
     *
     * The proposal is resolved again by correlation, actor and source, so a
     * forged DTO cannot confirm anything. The module executes the action; the
     * runtime only authorizes, checks validity and audits the outcome.
     */
    public function confirm(User $actor, SamProposal $proposal, string $phrase): SamProposalResult
    {
        if (! $this->canUse($actor)) {
            $this->auditConfirmation($actor, $proposal, 'denied', ['reason' => 'forbidden']);

            return SamProposalResult::failed(__('You cannot confirm this proposal.'));
        }

        if (trim($phrase) === '') {
            $this->auditConfirmation($actor, $proposal, 'denied', ['reason' => 'phrase_required']);

            return SamProposalResult::failed(__('The confirmation phrase is required.'));
        }

        $resolved = $this->resolveProposal($actor, $proposal);

        if ($resolved === null) {
            $this->auditConfirmation($actor, $proposal, 'denied', ['reason' => 'not_found']);

            return SamProposalResult::failed(__('This proposal is no longer available.'));
        }

        [$presenter, $current] = $resolved;

        if (! $current->canConfirm) {
            $this->auditConfirmation($actor, $current, 'denied', ['reason' => 'not_confirmable']);

            return SamProposalResult::failed(__('This proposal cannot be confirmed right now.'));
        }

        if ($current->expiresAt !== null && $current->expiresAt->isPast()) {
            $this->auditConfirmation($actor, $current, 'expired');

            return SamProposalResult::expired(__('This proposal has expired.'));
        }

        try {
            $result = $presenter->confirm($current, $actor, $phrase);
        } catch (Throwable $exception) {
            report($exception);

            $this->auditConfirmation($actor, $current, 'failed', ['reason' => 'error']);

            return SamProposalResult::failed(__('The confirmation could not be completed.'));
        }

        $this->auditConfirmation($actor, $current, $result->status);

        return $result;
    }

    /**
     * @return array{0: SamProposalPresenter, 1: SamProposal}|null
     */
    private function resolveProposal(User $actor, SamProposal $proposal): ?array
    {
        foreach ($this->registry->contributors() as $contributor) {
            $presenter = $contributor->proposals();

            if ($presenter === null) {
                continue;
            }

            foreach ($presenter->forCorrelation($proposal->correlationId, $actor) as $candidate) {
                if ($candidate->source === $proposal->source && $candidate->id === $proposal->id) {
                    return [$presenter, $candidate];
                }
            }
        }

        return null;
    }

    /**
     * Whether the conversation belongs to the actor's Sam profile.
     */
    private function owns(User $actor, AiConversation $conversation): bool
    {
        return (string) $conversation->user_id === (string) $actor->getKey()
            && $conversation->profile === self::PROFILE;
    }

    /**
     * Previous turns, bounded by configuration and oldest first.
     *
     * @return list<Message>
     */
    private function contextMessages(AiConversation $conversation): array
    {
        $messages = $conversation->messages()
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($this->maxContextMessages())
            ->get()
            ->reverse();

        return array_values($messages
            ->map(fn (AiChatMessage $message): Message => new Message($message->role, $message->content))
            ->all());
    }

    private function guaranteeDeclaration(string $text, bool $firstAssistantTurn): string
    {
        if (! $firstAssistantTurn) {
            return $text;
        }

        $declaration = trim((string) $this->registry->profile()?->declaration());

        if ($declaration === '' || str_contains($text, $declaration)) {
            return $text;
        }

        return $declaration."\n\n".$text;
    }

    private function prune(User $actor): void
    {
        $days = $this->retentionDays();

        if ($days <= 0) {
            return;
        }

        $conversationIds = AiConversation::query()
            ->where('user_id', $actor->getKey())
            ->where('profile', self::PROFILE)
            ->select('id');

        AiChatMessage::query()
            ->whereIn('conversation_id', $conversationIds)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    private function rejectTool(User $actor, string $source, mixed $tool, string $reason = 'not_declared'): void
    {
        $name = is_object($tool) ? $tool::class : get_debug_type($tool);

        app(RecordMcpInvocation::class)->handle(
            $actor,
            $name,
            'denied',
            SamTurn::correlationId(),
            null,
            [
                'channel' => 'sam',
                'source' => $source,
                'action' => 'tool_rejected',
                'reason' => $reason,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function auditConfirmation(User $actor, SamProposal $proposal, string $status, array $context = []): void
    {
        app(RecordMcpInvocation::class)->handle(
            $actor,
            'sam_confirm',
            $status,
            $proposal->correlationId !== '' ? $proposal->correlationId : SamTurn::correlationId(),
            null,
            array_merge([
                'channel' => 'sam',
                'source' => $proposal->source,
                'action' => 'confirm',
                'proposal_id' => $proposal->id,
            ], $context),
        );
    }

    private function errorKey(Throwable $exception): string
    {
        return match (true) {
            $exception instanceof AiQuotaExceededException => 'quota',
            $exception instanceof InsufficientCreditsException => 'credits',
            $exception instanceof AiNotConfiguredException => 'not_configured',
            $exception instanceof ModelNotAllowedException => 'not_configured',
            $exception instanceof UnsupportedCapabilityException => 'tools_unsupported',
            default => 'failed',
        };
    }

    /**
     * Audit the deletion of threads: counts only, never message content.
     *
     * @param  array<string, int>  $counts
     */
    private function auditLifecycle(User $actor, string $action, array $counts): void
    {
        app(RecordMcpInvocation::class)->handle(
            $actor,
            'sam_conversations',
            'completed',
            SamTurn::correlationId(),
            null,
            array_merge([
                'channel' => 'sam',
                'action' => $action,
            ], $counts),
        );
    }
}
