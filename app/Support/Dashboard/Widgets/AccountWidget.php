<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\ActionData;
use App\Support\Dashboard\DashboardSection;
use App\Support\Dashboard\Widget;
use App\Support\Dashboard\WidgetData;
use App\Support\Dashboard\WidgetItem;
use App\Support\Dashboard\WidgetType;

/**
 * Account shortcuts every authenticated identity keeps.
 */
class AccountWidget extends Widget
{
    public function id(): string
    {
        return 'lnx.account';
    }

    public function section(): DashboardSection
    {
        return DashboardSection::Access;
    }

    public function type(): WidgetType
    {
        return WidgetType::Action;
    }

    public function title(): ?string
    {
        return __('Your account');
    }

    public function order(): int
    {
        return 10;
    }

    public function span(): int
    {
        return 12;
    }

    public function resolve(User $actor): ?WidgetData
    {
        return new ActionData([
            new WidgetItem(
                __('Profile'),
                __('Update your personal information.'),
                href: route('profile.edit'),
                icon: 'user-circle',
                color: 'violet',
            ),
            new WidgetItem(
                __('Security'),
                __('Manage your sign-in security.'),
                href: route('security.edit'),
                icon: 'lock-closed',
                color: 'emerald',
            ),
            new WidgetItem(
                __('Support'),
                __('Review your own support requests.'),
                href: route('support.index'),
                icon: 'inbox',
                color: 'rose',
            ),
        ]);
    }
}
