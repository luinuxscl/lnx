<?php

namespace App\Ai\Sam;

use App\Models\SamModeCeiling;
use App\Models\User;

/**
 * Resolves the effective mode of an identity.
 *
 * The deployment sets a global ceiling, a role may lower it and the person may
 * lower it again. The effective mode is always the minimum, so no actor —root
 * included— reaches a mode above the deployment ceiling.
 */
class SamModeResolver
{
    /**
     * Ceiling declared by the deployment.
     */
    public function global(): SamMode
    {
        return SamMode::parse(config('lnx_sam.max_mode', SamMode::ReadOnly->value));
    }

    /**
     * Effective mode of the actor, never above the deployment ceiling.
     */
    public function for(?User $actor): SamMode
    {
        $ceiling = $this->global();

        if (! $actor instanceof User) {
            return SamMode::ReadOnly;
        }

        $roleCeiling = $this->roleCeiling($actor);

        return $roleCeiling === null ? $ceiling : $ceiling->atMost($roleCeiling);
    }

    /**
     * Highest ceiling among the roles of the actor, or null when none applies.
     *
     * A person with several roles acts with the union of their permissions, so
     * their ceiling is the highest of their roles. The deployment ceiling is
     * applied afterwards and always wins.
     */
    private function roleCeiling(User $actor): ?SamMode
    {
        $roles = $actor->getRoleNames()->all();

        if ($roles === []) {
            return null;
        }

        $ceilings = SamModeCeiling::query()
            ->whereIn('role', $roles)
            ->get(['role', 'mode'])
            ->map(fn (SamModeCeiling $ceiling): SamMode => $ceiling->mode);

        $highest = null;

        foreach ($ceilings as $mode) {
            $highest = $highest === null || $mode->rank() > $highest->rank() ? $mode : $highest;
        }

        return $highest;
    }
}
