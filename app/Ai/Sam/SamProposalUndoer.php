<?php

namespace App\Ai\Sam;

use App\Models\User;

/**
 * Optional capability of a proposal presenter: a confirmed, reversible change
 * can be undone once.
 *
 * It is a separate contract on purpose. A module that only proposes drafts
 * implements {@see SamProposalPresenter}; a module that really keeps the
 * previous state and can replay it implements this one too. The runtime only
 * offers the undo of a module that answers here, so the panel never invites a
 * reversal the module cannot perform.
 *
 * The presenter revalidates ownership, the window and the freshness of the
 * record; the runtime only routes and audits.
 */
interface SamProposalUndoer
{
    /**
     * Undo the confirmed change behind the proposal, one single time.
     *
     * A second call must be refused by the module. The result uses the same
     * vocabulary as a confirmation: `confirmed` when the change was undone,
     * `duplicate` when it had already been undone, `expired` when the window
     * elapsed and `failed` for anything else.
     */
    public function undo(SamProposal $proposal, User $actor): SamProposalResult;
}
