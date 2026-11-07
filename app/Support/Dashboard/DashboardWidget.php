<?php

namespace App\Support\Dashboard;

use App\Models\User;

/**
 * What a package declares for the dashboard.
 *
 * A package never builds its own dashboard page: it declares widgets, their
 * section and their permission, and returns typed data for the actor. Nothing
 * is exposed just because the package sits in `vendor/`; the declaration is
 * explicit and the host filters it again on every render.
 */
interface DashboardWidget
{
    /**
     * Unique id, prefixed with the package source (for example `crm.contacts`).
     */
    public function id(): string;

    /**
     * Package source that owns the widget.
     */
    public function source(): string;

    /**
     * Fixed section the widget belongs to.
     */
    public function section(): DashboardSection;

    /**
     * Shape the host must render.
     */
    public function type(): WidgetType;

    /**
     * Human readable title, already translated by the package.
     */
    public function title(): ?string;

    /**
     * Optional description shown under the title.
     */
    public function description(): ?string;

    /**
     * Simple ability the actor must hold, or null when the widget has no
     * additional gate beyond authentication.
     */
    public function permission(): ?string;

    /**
     * Whether the actor may see the widget.
     *
     * The default checks `permission()`; a widget backed by a model policy
     * overrides it with `Gate::allows('viewAny', Model::class)`.
     */
    public function visible(User $actor): bool;

    /**
     * Order inside the section; ties are resolved by id.
     */
    public function order(): int;

    /**
     * Width in the 12-column grid, between 1 and 12.
     */
    public function span(): int;

    /**
     * Typed payload for the actor, or null to hide the widget.
     *
     * The package is responsible for scoping every query to the actor; the
     * host only checks `visible()` before calling this method.
     */
    public function resolve(User $actor): ?WidgetData;
}
