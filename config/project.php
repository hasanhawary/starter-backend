<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Project Information
    |--------------------------------------------------------------------------
    */
    'project' => [
        'name' => env('APP_NAME', 'MyProject'),
        'version' => env('APP_VERSION', '1.0.0'),
        'env' => env('APP_ENV', 'production'),
        'locale' => 'ar', // default language
        'fallback_locale' => 'en',
        'timezone' => env('APP_TIMEZONE', 'Africa/Cairo'),
        'currency' => 'EGP',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s',
        'datetime_format' => 'Y-m-d H:i:s',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'login_methods' => [
            'password' => true,
            'otp' => env('AUTH_LOGIN_OTP', false),
        ],

        'encryption' => [
            'key' => env('FRONT_SHARED_KEY', 'base64:JdVOOP6UH8ly/dtvvNqNvtdLtFsbMpyNp0oGMXW2wA8=%'),

            // Incoming data from frontend
            'incoming' => [
                'password' => false,
                'otp' => false,
            ],

            // Outgoing data to frontend
            'outgoing' => [
                'roles' => true,
                'permissions' => true,
                'token' => true,
                'user_data' => false,
            ],
        ],

        'otp' => [
            'required_for' => [
                'admin' => false,
                'user' => false,
            ],
            'fallback_to_password' => true,
        ],

        'max_login_attempts' => 5,
        'lockout_time' => 180, // seconds
        'default_role' => 'default_role',
        'default_phone_code_id' => 1,
        'strong_password' => config('STRONG_PASSWORD', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'default' => '1111',        // Force a fixed OTP (for testing)
        'length' => 6,            // Number of characters
        'type' => 'alpha',      // numeric | alpha | alphanumeric
        'delay' => '30',          // seconds between sends
        'expires_in' => 10,       // minutes
        'max_attempts' => 5,
        'lock_time' => 120        //seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Defaults
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'per_page' => 10,
        'max' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Uploads
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'disk' => env('FILESYSTEM_DISK', 'public'),
        'max_size' => 2048, // KB
        'allowed_images' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Realtime
    |--------------------------------------------------------------------------
    */
    'realtime' => [
        'enabled' => env('REALTIME_ENABLED', true),
    ],
];
