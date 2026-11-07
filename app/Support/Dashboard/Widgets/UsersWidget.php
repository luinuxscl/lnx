<?php

namespace App\Support\Dashboard\Widgets;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Support\Dashboard\DashboardSection;
use App\Support\Dashboard\StatData;
use App\Support\Dashboard\StatsData;
use App\Support\Dashboard\Widget;
use App\Support\Dashboard\WidgetData;
use App\Support\Dashboard\WidgetItem;
use App\Support\Dashboard\WidgetType;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;

/**
 * Actionable account metrics for administrators.
 *
 * Root sees the tool cards instead of the summaries, so this widget answers
 * null for root even when the role check is evaluated again on refresh.
 */
class UsersWidget extends Widget
{
    public function id(): string
    {
        return 'lnx.users';
    }

    public function section(): DashboardSection
    {
        return DashboardSection::Stats;
    }

    public function type(): WidgetType
    {
        return WidgetType::Stats;
    }

    public function title(): ?string
    {
        return __('Users');
    }

    public function description(): ?string
    {
        return __('Review the accounts and act on what needs attention.');
    }

    public function order(): int
    {
        return 10;
    }

    public function span(): int
    {
        return 12;
    }

    public function visible(User $actor): bool
    {
        return ! $actor->hasRole(Role::Root)
            && $actor->hasAnyRole([Role::Root, Role::Admin])
            && Gate::allows('viewAny', User::class);
    }

    public function resolve(User $actor): ?WidgetData
    {
        if (! $this->visible($actor)) {
            return null;
        }

        $counts = User::query()->selectRaw(
            'COUNT(*) AS total, '
            .'SUM(CASE WHEN suspended_at IS NULL THEN 1 ELSE 0 END) AS active, '
            .'SUM(CASE WHEN suspended_at IS NOT NULL THEN 1 ELSE 0 END) AS suspended, '
            .'SUM(CASE WHEN email_verified_at IS NULL THEN 1 ELSE 0 END) AS unverified, '
            .'SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS recent',
            [now()->subDays(7)],
        )->first()?->toArray() ?? [];

        $pendingInvitations = Gate::allows('viewAny', UserInvitation::class)
            ? UserInvitation::query()
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->count()
            : null;

        $items = [
            new StatData(
                (string) Number::format((int) ($counts['total'] ?? 0)),
                __('Total users'),
                icon: 'users',
                color: 'blue',
                href: route('admin.users.index'),
            ),
            new StatData(
                (string) Number::format((int) ($counts['active'] ?? 0)),
                __('Active users'),
                icon: 'check-circle',
                color: 'emerald',
                href: route('admin.users.index', ['status' => 'active']),
            ),
            new StatData(
                (string) Number::format((int) ($counts['suspended'] ?? 0)),
                __('Suspended users'),
                icon: 'user-minus',
                color: 'rose',
                href: route('admin.users.index', ['status' => 'suspended']),
            ),
            new StatData(
                (string) Number::format((int) ($counts['unverified'] ?? 0)),
                __('Unverified users'),
                icon: 'exclamation-triangle',
                color: 'amber',
                href: route('admin.users.index', ['verified' => 'unverified']),
            ),
            new StatData(
                (string) Number::format(User::role(Role::Admin->value)->count()),
                __('Administrators'),
                icon: 'shield-check',
                color: 'violet',
                href: route('admin.users.index', ['role' => 'admin']),
            ),
            new StatData(
                (string) Number::format((int) ($counts['recent'] ?? 0)),
                __('Signups (7 days)'),
                icon: 'user-plus',
                color: 'cyan',
                href: route('admin.users.index', ['sort' => 'registered', 'dir' => 'desc']),
            ),
        ];

        if ($pendingInvitations !== null) {
            $items[] = new StatData(
                (string) Number::format($pendingInvitations),
                __('Pending invitations'),
                icon: 'clock',
                color: 'amber',
                href: route('admin.users.invitations'),
            );
        }

        $actions = [
            new WidgetItem(
                __('View users'),
                href: route('admin.users.index'),
                icon: 'arrow-right',
            ),
        ];

        if (Gate::allows('create', UserInvitation::class)) {
            $actions[] = new WidgetItem(
                __('Invite user'),
                href: route('admin.users.index', ['invite' => 1]),
                icon: 'user-plus',
            );
        }

        return new StatsData(
            $items,
            actions: $actions,
            description: $pendingInvitations === 0
                ? __('No invitations are awaiting acceptance.')
                : null,
        );
    }
}
