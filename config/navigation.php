<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Autopublish package menus
    |--------------------------------------------------------------------------
    |
    | When enabled, every route tagged with the `menu` macro is autopublished
    | in its declared audiences unless the tag explicitly opts out with
    | `'auto' => false`. A route can always opt in individually with
    | `'auto' => true`, even when this global switch is off.
    |
    */

    'autopublish' => env('NAVIGATION_AUTOPUBLISH', false),

    /*
    |--------------------------------------------------------------------------
    | Resolved structure cache TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | How long the resolved sidebar tree is cached per audience. Any write to
    | a group or item, and every reconciliation, bumps the cache version.
    |
    */

    'cache_ttl' => (int) env('NAVIGATION_CACHE_TTL', 3600),

];
