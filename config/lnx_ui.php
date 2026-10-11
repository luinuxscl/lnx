<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shared UI package repository
    |--------------------------------------------------------------------------
    |
    | The Composer package that holds the shared, presentational UI components
    | (namespace x-lnx-ui::). This package is not part of the generic packages
    | registry: it keeps its dedicated page (`/admin/packages/lnx-ui`) with the
    | design gallery. The page reads the installed version from Composer and the
    | latest tag from the GitHub API using the shared `packages` configuration.
    |
    */

    'repository' => env('LNX_UI_REPOSITORY', 'luinuxscl/lnx-ui'),

];
