<?php

namespace Tests\Fixtures\Sam;

use App\Ai\Sam\SamPreferences;
use App\Ai\Sam\SamPreferencesPresenter;
use App\Models\User;

class FakeSamPreferences implements SamPreferencesPresenter
{
    public static ?SamPreferences $preferences = null;

    public function preferences(User $actor): ?SamPreferences
    {
        return self::$preferences;
    }
}
