<?php

return [
    'name' => 'Showcase',

    /*
    |--------------------------------------------------------------------------
    | Media Directories
    |--------------------------------------------------------------------------
    | Where the module's uploaded files are stored, relative to the configured
    | media disk.
    */
    'media' => [
        'cover' => 'showcase/covers',
        'category_icon' => 'showcase/categories',
    ],

    /*
    |--------------------------------------------------------------------------
    | Expiry Reminder Window
    |--------------------------------------------------------------------------
    | Default number of days `Showcase::expiringWithin()` looks ahead when the
    | caller passes none.
    */
    'expiry_reminder_days' => 7,
];
