<?php

return [
    'allowed_models' => [
        'App\\Models\\Country',
        'App\\Models\\Notification',
        'App\\Models\\Permission',
        'App\\Models\\Role',
        'App\\Models\\Setting',
        'App\\Models\\User',
        'App\\Models\\UserSetting',
    ],
    'blocked_models' => [
        'Illuminate\\Database\\Eloquent\\Model',
        'App\\Models\\PersonalAccessToken',
        'App\\Models\\PasswordResetToken',
        'Laravel\\Sanctum\\PersonalAccessToken',
        'Illuminate\\Notifications\\DatabaseNotification',
    ],
    'blocked_fields' => [
        'password',
        'remember_token',
        'token',
        'secret',
        'api_key',
        'access_token',
        'refresh_token',
        'private_key',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'otp_data',
    ],
    'models' => [
        'App\\Models\\Country' => [
            'table' => 'countries',
            'fillable' => [
                'name',
                'nationality',
                'flag',
                'code',
                'phone_code',
                'phone_length',
                'is_active',
            ],
            'casts' => [
                'id' => 'int',
            ],
            'relationships' => [],
            'scopes' => [
                'active',
                'whereLocale',
                'whereLocales',
                'whereJsonContainsLocale',
                'whereJsonContainsLocales',
            ],
            'soft_deletes' => true,
        ],
        'App\\Models\\Notification' => [
            'table' => 'notifications',
            'fillable' => [
                'type',
                'notifiable_type',
                'notifiable_id',
                'data',
                'read_at',
                'open_at',
            ],
            'casts' => [
                'id' => 'string',
                'data' => 'array',
            ],
            'relationships' => [],
            'scopes' => [
                'forCurrentUser',
            ],
            'soft_deletes' => false,
        ],
        'App\\Models\\Permission' => [
            'table' => 'permissions',
            'fillable' => [
                'name',
                'guard_name',
                'display_name',
                'group',
            ],
            'casts' => [
                'id' => 'int',
            ],
            'relationships' => [],
            'scopes' => [
                'whereLocale',
                'whereLocales',
                'whereJsonContainsLocale',
                'whereJsonContainsLocales',
            ],
            'soft_deletes' => false,
        ],
        'App\\Models\\Role' => [
            'table' => 'roles',
            'fillable' => [
                'name',
                'guard_name',
                'display_name',
                'is_active',
                'created_by',
            ],
            'casts' => [
                'id' => 'int',
                'is_active' => 'boolean',
            ],
            'relationships' => [
                [
                    'method' => 'creator',
                    'type' => 'belongsTo',
                    'related_model' => 'App\\Models\\User',
                ],
                [
                    'method' => 'roleUsers',
                    'type' => 'morphToMany',
                    'related_model' => 'App\\Models\\User',
                ],
            ],
            'scopes' => [
                'whereLocale',
                'whereLocales',
                'whereJsonContainsLocale',
                'whereJsonContainsLocales',
                'related',
                'excludeRoot',
                'excludeLoggedInRole',
            ],
            'soft_deletes' => false,
        ],
        'App\\Models\\Setting' => [
            'table' => 'settings',
            'fillable' => [
                'key',
                'value',
                'group',
                'type',
                'label',
                'placeholder',
                'is_multi_lang',
                'is_env',
            ],
            'casts' => [
                'id' => 'int',
                'type' => 'App\\Enum\\Global\\SettingTypeEnum',
                'is_multi_lang' => 'boolean',
                'is_env' => 'boolean',
            ],
            'relationships' => [],
            'scopes' => [
                'public',
                'whereLocale',
                'whereLocales',
                'whereJsonContainsLocale',
                'whereJsonContainsLocales',
            ],
            'soft_deletes' => false,
        ],
        'App\\Models\\User' => [
            'table' => 'users',
            'fillable' => [
                'name',
                'email',
                'phone_code_id',
                'phone',
                'avatar',
                'gender',
                'is_active',
                'last_login',
                'ldap_name',
                'guid',
                'uid',
                'created_by',
            ],
            'casts' => [
                'id' => 'int',
                'email_verified_at' => 'datetime',
                'last_login' => 'datetime',
                'is_active' => 'boolean',
                'password' => 'hashed',
                'gender' => 'App\\Enum\\User\\UserGenderEnum',
                'otp_data' => 'array',
            ],
            'relationships' => [
                [
                    'method' => 'creator',
                    'type' => 'belongsTo',
                    'related_model' => 'App\\Models\\User',
                ],
                [
                    'method' => 'phoneCode',
                    'type' => 'belongsTo',
                    'related_model' => 'App\\Models\\Country',
                ],
                [
                    'method' => 'settings',
                    'type' => 'hasOne',
                    'related_model' => 'App\\Models\\UserSetting',
                ],
                [
                    'method' => 'tokens',
                    'type' => 'morphMany',
                    'related_model' => 'Sanctum::$personalAccessTokenModel',
                ],
                [
                    'method' => 'roles',
                    'type' => 'belongsToMany',
                    'related_model' => 'config(\'permission.models.role\'',
                ],
                [
                    'method' => 'permissions',
                    'type' => 'belongsToMany',
                    'related_model' => 'config(\'permission.models.permission\'',
                ],
                [
                    'method' => 'activities',
                    'type' => 'morphMany',
                    'related_model' => 'ActivitylogServiceProvider::determineActivityModel(',
                ],
                [
                    'method' => 'notifications',
                    'type' => 'morphMany',
                    'related_model' => 'Illuminate\\Notifications\\DatabaseNotification',
                ],
            ],
            'scopes' => [
                'role',
                'withoutRole',
                'permission',
                'withoutPermission',
                'related',
                'excludeLoggedInUser',
                'excludeRoot',
                'withRole',
            ],
            'soft_deletes' => true,
        ],
        'App\\Models\\UserSetting' => [
            'table' => 'user_settings',
            'fillable' => [
                'user_id',
                'setting',
            ],
            'casts' => [
                'id' => 'int',
                'setting' => 'array',
            ],
            'relationships' => [
                [
                    'method' => 'user',
                    'type' => 'belongsTo',
                    'related_model' => 'App\\Models\\User',
                ],
            ],
            'scopes' => [],
            'soft_deletes' => false,
        ],
    ],
    'routes' => [
        [
            'uri' => '/api/ai-chat/messages',
            'methods' => [
                'POST',
            ],
            'controller' => 'AiChat\\Http\\Controllers\\AiChatController',
            'action' => 'sendMessage',
            'name' => null,
        ],
        [
            'uri' => '/api/ai-chat/messages/stream',
            'methods' => [
                'POST',
            ],
            'controller' => 'AiChat\\Http\\Controllers\\AiChatController',
            'action' => 'streamMessage',
            'name' => null,
        ],
        [
            'uri' => '/api/ai-chat/conversations',
            'methods' => [
                'GET',
            ],
            'controller' => 'AiChat\\Http\\Controllers\\AiChatController',
            'action' => 'listConversations',
            'name' => null,
        ],
        [
            'uri' => '/api/ai-chat/conversations/{id}',
            'methods' => [
                'GET',
            ],
            'controller' => 'AiChat\\Http\\Controllers\\AiChatController',
            'action' => 'getConversation',
            'name' => null,
        ],
        [
            'uri' => '/api/ai-chat/conversations/{id}',
            'methods' => [
                'DELETE',
            ],
            'controller' => 'AiChat\\Http\\Controllers\\AiChatController',
            'action' => 'deleteConversation',
            'name' => null,
        ],
        [
            'uri' => '/api/ai-chat/feedback',
            'methods' => [
                'POST',
            ],
            'controller' => 'AiChat\\Http\\Controllers\\AiChatController',
            'action' => 'submitFeedback',
            'name' => null,
        ],
        [
            'uri' => '/api/ai-chat/tools',
            'methods' => [
                'GET',
            ],
            'controller' => 'AiChat\\Http\\Controllers\\ToolController',
            'action' => 'listTools',
            'name' => null,
        ],
        [
            'uri' => '/api/ai-chat/tools/{name}',
            'methods' => [
                'GET',
            ],
            'controller' => 'AiChat\\Http\\Controllers\\ToolController',
            'action' => 'getToolSchema',
            'name' => null,
        ],
        [
            'uri' => '/sanctum/csrf-cookie',
            'methods' => [
                'GET',
            ],
            'controller' => 'Laravel\\Sanctum\\Http\\Controllers\\CsrfCookieController',
            'action' => 'show',
            'name' => 'sanctum.csrf-cookie',
        ],
        [
            'uri' => '/_ignition/health-check',
            'methods' => [
                'GET',
            ],
            'controller' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\HealthCheckController',
            'action' => '__invoke',
            'name' => 'ignition.healthCheck',
        ],
        [
            'uri' => '/_ignition/execute-solution',
            'methods' => [
                'POST',
            ],
            'controller' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\ExecuteSolutionController',
            'action' => '__invoke',
            'name' => 'ignition.executeSolution',
        ],
        [
            'uri' => '/_ignition/update-config',
            'methods' => [
                'POST',
            ],
            'controller' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\UpdateConfigController',
            'action' => '__invoke',
            'name' => 'ignition.updateConfig',
        ],
        [
            'uri' => '/api/captcha',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\Captcha\\CaptchaController',
            'action' => 'generateCaptcha',
            'name' => null,
        ],
        [
            'uri' => '/api/captcha/verify',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\Captcha\\CaptchaController',
            'action' => 'verifyCaptcha',
            'name' => null,
        ],
        [
            'uri' => '/api/login',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Auth\\LoginController',
            'action' => '__invoke',
            'name' => null,
        ],
        [
            'uri' => '/api/reset-password',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Auth\\ResetPasswordController',
            'action' => '__invoke',
            'name' => null,
        ],
        [
            'uri' => '/api/send-otp',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Auth\\OTPController',
            'action' => 'send',
            'name' => null,
        ],
        [
            'uri' => '/api/check-otp',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Auth\\OTPController',
            'action' => 'check',
            'name' => null,
        ],
        [
            'uri' => '/api/verify-otp',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Auth\\OTPController',
            'action' => 'verify',
            'name' => null,
        ],
        [
            'uri' => '/api/me',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Profile\\ProfileController',
            'action' => 'user',
            'name' => null,
        ],
        [
            'uri' => '/api/update-setting',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Profile\\ProfileController',
            'action' => 'updateSetting',
            'name' => null,
        ],
        [
            'uri' => '/api/update-profile',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Profile\\ProfileController',
            'action' => 'updateProfile',
            'name' => null,
        ],
        [
            'uri' => '/api/destroy-avatar',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Profile\\ProfileController',
            'action' => 'destroyAvatar',
            'name' => null,
        ],
        [
            'uri' => '/api/logout',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Auth\\LogoutController',
            'action' => '__invoke',
            'name' => null,
        ],
        [
            'uri' => '/api/permissions',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\PermissionController',
            'action' => 'index',
            'name' => null,
        ],
        [
            'uri' => '/api/roles/delete',
            'methods' => [
                'DELETE',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\RoleController',
            'action' => 'destroy',
            'name' => null,
        ],
        [
            'uri' => '/api/roles',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\RoleController',
            'action' => 'index',
            'name' => 'index',
        ],
        [
            'uri' => '/api/roles',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\RoleController',
            'action' => 'store',
            'name' => 'store',
        ],
        [
            'uri' => '/api/roles/{role}',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\RoleController',
            'action' => 'show',
            'name' => 'show',
        ],
        [
            'uri' => '/api/roles/{role}',
            'methods' => [
                'PUT',
                'PATCH',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\RoleController',
            'action' => 'update',
            'name' => 'update',
        ],
        [
            'uri' => '/api/countries/force-delete',
            'methods' => [
                'DELETE',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\DataEntry\\CountryController',
            'action' => 'forceDelete',
            'name' => null,
        ],
        [
            'uri' => '/api/countries/delete',
            'methods' => [
                'DELETE',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\DataEntry\\CountryController',
            'action' => 'destroy',
            'name' => null,
        ],
        [
            'uri' => '/api/countries/restore',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\DataEntry\\CountryController',
            'action' => 'restore',
            'name' => null,
        ],
        [
            'uri' => '/api/countries/toggle-active',
            'methods' => [
                'PUT',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\DataEntry\\CountryController',
            'action' => 'toggleActive',
            'name' => null,
        ],
        [
            'uri' => '/api/countries',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\DataEntry\\CountryController',
            'action' => 'index',
            'name' => 'index',
        ],
        [
            'uri' => '/api/countries',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\DataEntry\\CountryController',
            'action' => 'store',
            'name' => 'store',
        ],
        [
            'uri' => '/api/countries/{country}',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\DataEntry\\CountryController',
            'action' => 'show',
            'name' => 'show',
        ],
        [
            'uri' => '/api/countries/{country}',
            'methods' => [
                'PUT',
                'PATCH',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\DataEntry\\CountryController',
            'action' => 'update',
            'name' => 'update',
        ],
        [
            'uri' => '/api/settings',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\Setting\\SettingController',
            'action' => 'index',
            'name' => null,
        ],
        [
            'uri' => '/api/settings',
            'methods' => [
                'PUT',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\Setting\\SettingController',
            'action' => 'update',
            'name' => null,
        ],
        [
            'uri' => '/api/send-test-mail',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\Setting\\TestCredentialsController',
            'action' => 'testEmail',
            'name' => null,
        ],
        [
            'uri' => '/api/report',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\Report\\ReportController',
            'action' => '__invoke',
            'name' => null,
        ],
        [
            'uri' => '/api/activity-logs',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\ActivityLog\\ActivityLogController',
            'action' => 'index',
            'name' => null,
        ],
        [
            'uri' => '/api/activity-logs/{activity}',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\ActivityLog\\ActivityLogController',
            'action' => 'show',
            'name' => null,
        ],
        [
            'uri' => '/api/help-configs',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\Help\\HelpController',
            'action' => 'configs',
            'name' => null,
        ],
        [
            'uri' => '/api/help-models',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\Help\\HelpController',
            'action' => 'models',
            'name' => null,
        ],
        [
            'uri' => '/api/help-enums',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\Help\\HelpController',
            'action' => 'enums',
            'name' => null,
        ],
        [
            'uri' => '/api/notifications',
            'methods' => [
                'PUT',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\Notification\\NotificationController',
            'action' => 'update',
            'name' => null,
        ],
        [
            'uri' => '/api/notifications',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\Notification\\NotificationController',
            'action' => 'index',
            'name' => null,
        ],
        [
            'uri' => '/api/chunk-file',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\Global\\Chunk\\ChunkFileController',
            'action' => '__invoke',
            'name' => null,
        ],
        [
            'uri' => '/api/users/force-delete',
            'methods' => [
                'DELETE',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\UserController',
            'action' => 'forceDelete',
            'name' => null,
        ],
        [
            'uri' => '/api/users/delete',
            'methods' => [
                'DELETE',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\UserController',
            'action' => 'destroy',
            'name' => null,
        ],
        [
            'uri' => '/api/users/restore',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\UserController',
            'action' => 'restore',
            'name' => null,
        ],
        [
            'uri' => '/api/users/toggle-active',
            'methods' => [
                'PUT',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\UserController',
            'action' => 'toggleActive',
            'name' => null,
        ],
        [
            'uri' => '/api/users',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\UserController',
            'action' => 'index',
            'name' => 'index',
        ],
        [
            'uri' => '/api/users',
            'methods' => [
                'POST',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\UserController',
            'action' => 'store',
            'name' => 'store',
        ],
        [
            'uri' => '/api/users/{user}',
            'methods' => [
                'GET',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\UserController',
            'action' => 'show',
            'name' => 'show',
        ],
        [
            'uri' => '/api/users/{user}',
            'methods' => [
                'PUT',
                'PATCH',
            ],
            'controller' => 'App\\Http\\Controllers\\API\\User\\UserController',
            'action' => 'update',
            'name' => 'update',
        ],
    ],
    'summary' => [
        'models_count' => 8,
        'routes_count' => 64,
        'controllers_count' => 0,
        'services_count' => 9,
        'policies_count' => 2,
        'migrations_count' => 10,
        'tables_count' => 10,
    ],
];
