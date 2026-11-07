<?php

namespace App\Ai\Sam;

use App\Models\User;

/**
 * Module side of a proposal: it resolves its own drafts for the panel and
 * executes the human confirmation.
 *
 * `confirm()` delegates in the module's confirmation action; it never writes
 * directly from a DTO, and it re-validates ownership, validity and the phrase.
 */
interface SamProposalPresenter
{
    /**
     * Drafts of this module with the given correlation, visible to the actor.
     *
     * @return list<SamProposal>
     */
    public function forCorrelation(string $correlationId, User $actor): array;

    /**
     * Pending drafts of this module, visible to the actor, newest first.
     *
     * It is the read model behind the permanent approvals queue of the
     * dashboard: it is not tied to a conversation or a correlation.
     *
     * @return list<SamProposal>
     */
    public function pendingFor(User $actor, int $limit = 5): array;

    /**
     * Confirm a draft after the person confirmed it.
     *
     * The phrase is required by the runtime only for irreversible or bulk
     * drafts; the module re-checks its own policy.
     */
    public function confirm(SamProposal $proposal, User $actor, string $phrase): SamProposalResult;
}
