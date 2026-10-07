<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shared UI package repository
    |--------------------------------------------------------------------------
    |
    | The Composer package that holds the shared, presentational UI components
    | (namespace x-lnx-ui::). The admin package page reads the installed
    | version from Composer and the latest tag from the GitHub API.
    |
    */

    'repository' => env('LNX_UI_REPOSITORY', 'luinuxscl/lnx-ui'),

    /*
    |--------------------------------------------------------------------------
    | GitHub token
    |--------------------------------------------------------------------------
    |
    | The repository above is private, so checking for updates requires a
    | GitHub token with read access. Without a token the page still shows the
    | installed version but cannot resolve the latest available one.
    |
    */

    'token' => env('LNX_UI_GITHUB_TOKEN', env('GITHUB_TOKEN')),

    /*
    |--------------------------------------------------------------------------
    | Update check cache
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) a successful or failed update check is cached
    | before the GitHub API is queried again.
    |
    */

    'check_ttl' => (int) env('LNX_UI_UPDATE_CHECK_TTL', 3600),

];
