<?php

namespace App\Ai\Sam;

use App\Models\SamModeCeiling;
use App\Models\User;

/**
 * Resolves the effective mode of an identity.
 *
 * The deployment sets a global ceiling, a role may lower it and the person may
 * lower it again. The effective mode is always the minimum, so no actor —root
 * included— reaches a mode above the deployment ceiling. A preference is stored
 * as declared and clipped only here, so a ceiling that rises later applies
 * without rewriting it.
 */
class SamModeResolver
{
    /**
     * Guards a preference presenter that asks for the mode while it is resolved.
     */
    private static bool $resolvingPreference = false;

    /**
     * Ceiling declared by the deployment.
     */
    public function global(): SamMode
    {
        return SamMode::parse(config('lnx_sam.max_mode', SamMode::ReadOnly->value));
    }

    /**
     * Ceiling the actor can reach before their own preference: the minimum of
     * the deployment ceiling and the highest explicit ceiling among their roles.
     * An identity without a session is read only.
     */
    public function ceilingFor(?User $actor): SamMode
    {
        $ceiling = $this->global();

        if (! $actor instanceof User) {
            return SamMode::ReadOnly;
        }

        $roleCeiling = $this->roleCeiling($actor);

        return $roleCeiling === null ? $ceiling : $ceiling->atMost($roleCeiling);
    }

    /**
     * Effective mode of the actor, never above the deployment ceiling.
     */
    public function for(?User $actor): SamMode
    {
        $ceiling = $this->ceilingFor($actor);

        if (! $actor instanceof User || self::$resolvingPreference) {
            return $ceiling;
        }

        self::$resolvingPreference = true;

        try {
            $preference = app(SamRegistry::class)->preferencesFor($actor)->mode;
        } finally {
            self::$resolvingPreference = false;
        }

        return $preference instanceof SamMode ? $ceiling->atMost($preference) : $ceiling;
    }

    /**
     * Highest ceiling among the roles of the actor, or null when none applies.
     *
     * Only roles with an explicit row count: a role without one inherits the
     * deployment ceiling, so it never raises the ceiling of another role. When
     * several restricted roles apply, the highest of them wins because the
     * person acts with the union of their permissions; the deployment ceiling
     * is applied afterwards and always wins.
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
