<?php

namespace App\Ai\Sam;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Ai\Contracts\Tool;

/**
 * What a module declares for Sam, the platform agent.
 *
 * A module never builds its own chat: it declares what Sam knows about its
 * domain and which operations it may read or propose. Nothing is exposed just
 * because the package sits in `vendor/`; the declaration is explicit.
 */
interface SamContributor
{
    /**
     * Module source, prefixed with its package name (for example `crm`).
     */
    public function source(): string;

    /**
     * Human readable name of the module, used in the instructions.
     */
    public function label(): string;

    /**
     * Domain instructions: vocabulary, scope and limits.
     *
     * It is inserted into the system prompt and never shown to the person.
     */
    public function knowledge(User $actor): ?string;

    /**
     * Read-only tools available to the given identity.
     *
     * @return list<Tool>
     */
    public function readTools(User $actor): array;

    /**
     * Tools that return a draft and never write on their own.
     *
     * @return list<Tool>
     */
    public function proposeTools(User $actor): array;

    /**
     * Screen context of the current request, if any.
     */
    public function context(Request $request): ?SamContext;

    /**
     * Screen context of one row the person focused, if it is theirs to see.
     *
     * The browser sends only the resource and the id: the module resolves the
     * label and the filters again and answers null when the identity cannot
     * reach the record. Never trust a label sent from the client.
     *
     * @param  array<string, mixed>  $filters
     */
    public function rowContext(User $actor, string $resource, string $id, array $filters = []): ?SamContext;

    /**
     * Deterministic observations about the record of the context.
     *
     * Computed by the module in SQL, never by the model.
     *
     * @return list<SamSignal>
     */
    public function signals(User $actor, SamContext $context): array;

    /**
     * Conversation starters that respect the identity's permissions.
     *
     * @return list<string>
     */
    public function suggestions(User $actor, ?SamContext $context): array;

    /**
     * Presenter of this module's drafts, when it proposes writes.
     */
    public function proposals(): ?SamProposalPresenter;
}
