<?php

use HasanHawary\ExportBuilder\Http\Controllers\ExportController;
use HasanHawary\ExportBuilder\Http\Controllers\ExportJobController;
use HasanHawary\ExportBuilder\Services\ExportFileService;
use HasanHawary\ExportBuilder\Services\ExportPermissionResolver;
use HasanHawary\ExportBuilder\Services\ExportService;

return [
    /*
    |--------------------------------------------------------------------------
    | Export Namespace
    |--------------------------------------------------------------------------
    |
    | Namespace used to resolve export classes dynamically.
    | Example: page=user => App\Tools\Export\UserExport
    |
    */
    'namespace' => 'App\\Tools\\Export',

    /*
    |--------------------------------------------------------------------------
    | Streaming Chunk Size
    |--------------------------------------------------------------------------
    |
    | Number of rows fetched per chunk when streaming large exports via
    | lazyById(). Increase for faster exports, decrease to reduce peak
    | memory per chunk. Default: 500.
    |
    */
    'chunk_size' => 500,

    /*
    |--------------------------------------------------------------------------
    | Translation Settings
    |--------------------------------------------------------------------------
    |
    | Column headings are resolved from lang/{locale}/export.php.
    |
    */
    'trans_file' => 'export',

    /*
    |--------------------------------------------------------------------------
    | PDF Settings
    |--------------------------------------------------------------------------
    |
    | Static settings can be defined here.
    | Dynamic settings can be resolved using settings_resolver.
    |
    */
    'pdf' => [
        'settings' => [
            // 'logo_url' => null,
            // 'company_name' => null,
        ],

        /*
        |--------------------------------------------------------------------------
        | Settings Resolver
        |--------------------------------------------------------------------------
        |
        | Supported:
        | - Closure
        | - Invokable class string
        | - [ClassName::class, 'method']
        |
        | The resolver must return array.
        |
        */
        'settings_resolver' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Export Module
    |--------------------------------------------------------------------------
    |
    | The package can expose its own export endpoints, but it must never assume
    | ownership of a host application's /export routes. Keep this module behind
    | config so existing projects can disable, move, or replace it.
    |
    */
    'module' => [
        'enabled' => true,

        'routes' => [
            'enabled' => true,
            'middleware' => ['api', 'auth:sanctum'],
            'prefix' => 'api',
            'export_path' => 'export',
            'direct_path' => 'export-direct',
            'log_path' => 'export-log',
            'name_prefix' => 'export-builder.export.',
        ],

        'controllers' => [
            'direct' => ExportController::class,
            'jobs' => ExportJobController::class,
        ],

        'services' => [
            'export' => ExportService::class,
            'export_file' => ExportFileService::class,
            'permissions' => ExportPermissionResolver::class,
        ],

        'storage' => [
            'disk' => 'public',
            'path' => 'exports',
        ],

        /*
         * Disabled by default: a new project has no export permissions seeded yet.
         * Turn on and map the abilities per page once the roles config declares them.
         */
        'permissions' => [
            'enabled' => false,
            'abilities' => [
                'export' => 'export',
                'queue' => 'create-export-file',
                'view_all' => 'view-all-export-file',
                'view_own' => 'view-own-export-file',
                'delete' => 'delete-export-file',
            ],
            'pages' => [
                // 'user' => [
                //     'export' => 'export-user',
                //     'queue' => 'create-export-file',
                // ],
            ],
        ],
    ],
];
