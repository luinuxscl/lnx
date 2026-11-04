<?php

namespace App\Ai\Sam;

/**
 * Identity of the platform agent, supplied by the product package.
 *
 * The kit owns the runtime; the package that ships the agent owns its name,
 * its first-contact declaration and its persona. Without a registered profile
 * the runtime still works with the platform rules only.
 */
interface SamProfile
{
    /**
     * Fixed name of the agent.
     */
    public function name(): string;

    /**
     * First-contact declaration, literal: it is guaranteed on the first reply.
     */
    public function declaration(): string;

    /**
     * Persona and behaviour rules added to the platform rules.
     */
    public function instructions(): string;
}
