<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Package Routes
    |--------------------------------------------------------------------------
    |
    | Disabled: this application registers its own help-models / help-enums /
    | help-configs routes (App\Http\Controllers\API\Global\Help\HelpController)
    | so the responses go through successResponse() like every other endpoint.
    |
    */
    'routes' => [
        'enabled' => false,
        'prefix' => 'api',
        'middleware' => ['api', 'auth:sanctum'],
        'name_prefix' => '',
        'paths' => [
            'models' => 'help-models',
            'enums' => 'help-enums',
            'config' => 'help-configs',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Lookups
    |--------------------------------------------------------------------------
    |
    | Empty `namespaces`/`paths` fall back to the package defaults
    | (App\Models + app/Models).
    |
    */
    'models' => [
        'namespaces' => [],
        'paths' => [],
        'default_name_fields' => [
            'display_name',
            'title',
            'label',
            'name',
            'first_name',
            'last_name',
        ],
        'supported_locales' => null,
        'max_per_page' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Lookup Access Control
    |--------------------------------------------------------------------------
    |
    | `*` allows everything not explicitly blocked. Never remove a credential
    | column from `blocked_extra_columns`: the lookup endpoints are reachable by
    | any authenticated user, whatever their role.
    |
    */
    'access' => [
        'models' => ['*'],
        'blocked_models' => [],
        'extra_columns' => ['*'],
        'blocked_extra_columns' => [
            'password',
            'password_confirmation',
            'current_password',
            'remember_token',
            'api_token',
            'access_token',
            'refresh_token',
            'token',
            'secret',
            'secret_key',
        ],
        'relations' => ['*'],
        'blocked_relations' => [],
        'scopes' => ['*'],
        'blocked_scopes' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enum Lookups
    |--------------------------------------------------------------------------
    |
    | `allowed_methods` whitelists the enum methods `help-enums?method=` may
    | call. Add a project's own list methods here; anything not listed is
    | refused, so a crafted request can never reach an arbitrary static method.
    |
    */
    'enums' => [
        'namespaces' => [],
        'paths' => [],
        'default_method' => 'getList',
        'allowed_methods' => [
            'getList',
            'getValue',
            'group',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules
    |--------------------------------------------------------------------------
    |
    | Where the lookup manager resolves models and enums that live inside a
    | module rather than under app/.
    |
    */
    'modules' => [
        'enabled' => true,
        'path' => 'Modules',
        'namespace' => 'Modules',
        'model_namespace' => 'app\Models',
        'enum_namespace' => 'app\Enum',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Config Files and Keys
    |--------------------------------------------------------------------------
    |
    | This whitelist defines which config files can be accessed via the
    | config lookup API and which keys are safe to expose.
    |
    | - Use an empty array [] to allow all keys from a config file
    | - Use an array of specific keys to restrict access to only those keys
    | - Config files not listed here will return empty arrays
    |
    | Security Note: Never whitelist config files or keys that contain
    | sensitive credentials like passwords, tokens, secrets, or API keys.
    |
    | Example:
    | 'app' => ['name', 'env', 'debug'],  // Only allow specific keys
    | 'mail' => [],                       // Allow all keys (be careful!)
    | 'database' => ['default'],          // Only allow 'default' key
    |
    */
    'allowed_configs' => [
        /*
         * Expose the discovery config so the frontend can retrieve the
         * available filter and sorting definitions per resource.
         *
         * Usage:
         *   GET /help-configs?configs[0][name]=discovery
         *   GET /help-configs?configs[0][name]=discovery&configs[0][keys][0]=filters
         */
        'discovery' => [
            'filters',
            'sorting',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Root Excluded Models
    |--------------------------------------------------------------------------
    |
    | This array defines which models should automatically have the
    | "excludeRoot" scope applied when querying.
    |
    | Any model listed here must implement a scope method named `scopeExcludeRoot`
    | in order for this functionality to work correctly.
    |
    */
    'root_excluded_models' => [

    ],
];
