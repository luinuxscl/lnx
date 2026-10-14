<?php

namespace App\Enums;

enum NavigationRegion: string
{
    case Main = 'main';

    case Bottom = 'bottom';

    public function label(): string
    {
        return match ($this) {
            self::Main => 'Main',
            self::Bottom => 'Bottom',
        };
    }
}
