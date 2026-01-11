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
            'otp' => env('AUTH_LOGIN_OTP', true),
        ],

        'encryption' => [
            'key' => env('FRONT_SHARED_KEY', 'default_secret_key'),

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
    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP Configuration
    |--------------------------------------------------------------------------
    */
    'ldap' => [
        'active' => env('LDAP_ACTIVE', false), // enable or disable LDAP login
        'type' => env('LDAP_TYPE', 'ad'),    // ad or openldap
        'local' => env('LDAP_LOCAL', true),   // true = OpenLDAP, false = AD
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'default' => null,        // Force a fixed OTP (for testing)
        'length' => 6,            // Number of characters
        'type' => 'alpha',      // numeric | alpha | alphanumeric
        'delay' => null,          // seconds between sends
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
        'per_page' => 15,
        'max' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Uploads
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'disk' => env('FILESYSTEM_DISK', 'public'),
        'max_size' => 2048, // KB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'default' => env('CACHE_DRIVER', 'file'),
        'ttl' => 60, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'channel' => env('LOG_CHANNEL', 'stack'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications / Email / SMS
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'from_email' => env('MAIL_FROM_ADDRESS', 'no-reply@myproject.com'),
        'from_name' => env('MAIL_FROM_NAME', 'MyProject'),
        'sms_provider' => env('SMS_PROVIDER', 'twilio'),
    ]
];
