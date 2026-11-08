<?php

namespace App\Support\Dashboard;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Sensible defaults for a dashboard widget.
 *
 * A widget only overrides what it needs: the source is derived from the id,
 * the title is optional and the visibility checks `permission()` unless the
 * package has a model policy to evaluate.
 */
abstract class Widget implements DashboardWidget
{
    public function source(): string
    {
        $id = $this->id();
        $position = strpos($id, '.');

        return $position === false ? '' : substr($id, 0, $position);
    }

    public function title(): ?string
    {
        return null;
    }

    public function description(): ?string
    {
        return null;
    }

    public function permission(): ?string
    {
        return null;
    }

    public function visible(User $actor): bool
    {
        $permission = $this->permission();

        return $permission === null || Gate::allows($permission);
    }

    public function order(): int
    {
        return 0;
    }

    public function span(): int
    {
        return 4;
    }
}
