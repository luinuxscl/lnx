<?php

namespace App\Enums;

enum NavigationItemType: string
{
    case Route = 'route';

    case Link = 'link';
}
