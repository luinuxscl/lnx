<?php

namespace App\Support\Dashboard;

/**
 * Marker for the typed payload a widget returns.
 *
 * The concrete DTO must match the widget type; the host renders it with the
 * components of `lnx-ui` and never trusts markup from the package.
 */
interface WidgetData {}
