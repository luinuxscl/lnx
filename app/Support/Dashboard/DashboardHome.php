<?php

namespace App\Support\Dashboard;

use App\Models\User;

/**
 * A package may replace the generic dashboard welcome and widget sections
 * with its own Livewire surface while the host keeps the access section.
 */
interface DashboardHome
{
    public function source(): string;

    /**
     * When several homes are visible, the highest priority wins.
     */
    public function priority(): int;

    public function visible(User $actor): bool;

    /**
     * Registered Livewire component name (for example `luinuxscl.crm.livewire.today`).
     */
    public function livewireComponent(): string;
}
