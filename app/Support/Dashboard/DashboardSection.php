<?php

namespace App\Support\Dashboard;

/**
 * Fixed sections the dashboard host renders, in this order.
 *
 * A package never creates a section: it declares which one its widget belongs
 * to, so the page keeps a predictable structure across products.
 */
enum DashboardSection: string
{
    case Sam = 'sam';
    case Stats = 'stats';
    case Access = 'access';

    public function label(): string
    {
        return match ($this) {
            self::Sam => __('Sam'),
            self::Stats => __('Stats'),
            self::Access => __('Access'),
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::Sam => 10,
            self::Stats => 20,
            self::Access => 30,
        };
    }
}
