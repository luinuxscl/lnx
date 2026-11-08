<?php

namespace Tests\Fixtures\Sam;

use App\Models\User;
use Luinuxscl\LnxSam\SamPreferences;
use Luinuxscl\LnxSam\SamPreferencesPresenter;

class FakeSamPreferences implements SamPreferencesPresenter
{
    public static ?SamPreferences $preferences = null;

    public function preferences(User $actor): ?SamPreferences
    {
        return self::$preferences;
    }
}
