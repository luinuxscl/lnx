<?php

namespace Tests\Fixtures\Sam;

use App\Models\User;
use Luinuxscl\LnxSam\SamMode;
use Luinuxscl\LnxSam\SamModeResolver;
use Luinuxscl\LnxSam\SamPreferences;
use Luinuxscl\LnxSam\SamPreferencesPresenter;
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
