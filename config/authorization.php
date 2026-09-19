<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Root User
    |--------------------------------------------------------------------------
    |
    | Credentials used to seed the initial superuser account. This account is
    | only created when the root user seeder runs and never in production
    | unless the credentials are explicitly configured.
    |
    */

    'root_user' => [
        'name' => env('ROOT_USER_NAME', 'Root'),
        'email' => env('ROOT_USER_EMAIL'),
        'password' => env('ROOT_USER_PASSWORD'),
    ],

];
