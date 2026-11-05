<?php

namespace App\Ai\Sam;

use App\Models\User;

/**
 * Module side of the person's preferences.
 *
 * The package that owns the surface stores them; the runtime only reads them
 * to compose the instructions of the turn. A source without preferences for
 * the actor answers null and the defaults stay.
 */
interface SamPreferencesPresenter
{
    public function preferences(User $actor): ?SamPreferences;
}
