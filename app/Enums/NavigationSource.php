<?php

namespace App\Enums;

enum NavigationSource: string
{
    case Catalog = 'catalog';

    case Root = 'root';

    public function label(): string
    {
        return match ($this) {
            self::Catalog => 'Auto',
            self::Root => 'Root',
        };
    }
}
