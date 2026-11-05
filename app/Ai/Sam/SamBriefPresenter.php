<?php

namespace App\Ai\Sam;

use App\Models\User;

/**
 * Module side of the brief and the row insight.
 *
 * The module composes the deterministic brief from its own signals and the
 * actions the person can take. It never calls the model: the runtime may add
 * narrative on top when the provider is available.
 */
interface SamBriefPresenter
{
    /**
     * Brief of the record in focus, or null when the actor cannot see it.
     */
    public function brief(User $actor, SamContext $context): ?SamBrief;

    /**
     * Row insight of the record in focus, or null when the actor cannot see it.
     */
    public function insight(User $actor, SamContext $context): ?SamInsight;
}
