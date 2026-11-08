<?php

namespace App\Support\Dashboard;

/**
 * Shapes the host knows how to render.
 *
 * A package declares one of these and returns the matching DTO; it never
 * returns HTML, so the kit keeps the presentation and the accessibility rules.
 */
enum WidgetType: string
{
    case Stat = 'stat';
    case Stats = 'stats';
    case Series = 'series';
    case List = 'list';
    case Approvals = 'approvals';
    case Action = 'action';
    case Timeline = 'timeline';
    case Text = 'text';
}
