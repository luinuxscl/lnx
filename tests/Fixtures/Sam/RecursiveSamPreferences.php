<?php

namespace Tests\Fixtures\Sam;

use App\Ai\Sam\SamMode;
use App\Ai\Sam\SamModeResolver;
use App\Ai\Sam\SamPreferences;
use App\Ai\Sam\SamPreferencesPresenter;
use App\Models\User;
use RuntimeException;

/**
 * Presenter that asks for the mode while the resolver is reading preferences.
 */
class RecursiveSamPreferences implements SamPreferencesPresenter
{
    public static int $calls = 0;

    public function preferences(User $actor): ?SamPreferences
    {
        self::$calls++;

        if (self::$calls > 2) {
            throw new RuntimeException('preference resolution reentered');
        }

        app(SamModeResolver::class)->for($actor);

        return new SamPreferences(mode: SamMode::ReadOnly);
    }
}
