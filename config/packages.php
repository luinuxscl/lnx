<?php

/*
|--------------------------------------------------------------------------
| Installed packages
|--------------------------------------------------------------------------
|
| Shared configuration for the generic packages page (`/admin/packages`) and
| for the version check used by every registered package (and by the lnx-ui
| page). Each package reads the installed version from Composer and the latest
| tag from the GitHub API. Private repositories require a token with read
| access; without it the pages still show the installed version but cannot
| resolve the latest available one.
|
*/

return [

    'token' => env('PACKAGES_GITHUB_TOKEN', env('LNX_UI_GITHUB_TOKEN', env('GITHUB_TOKEN'))),

    'check_ttl' => (int) env('PACKAGES_UPDATE_CHECK_TTL', 3600),

];
