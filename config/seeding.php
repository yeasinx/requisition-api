<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeder Credentials
    |--------------------------------------------------------------------------
    |
    | These values are used by DatabaseSeeder to create the initial Super Admin
    | account. Set SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD in your .env file
    | before running `php artisan db:seed`.
    |
    */

    'admin_email' => env('SEED_ADMIN_EMAIL'),

    'admin_password' => env('SEED_ADMIN_PASSWORD'),

];
