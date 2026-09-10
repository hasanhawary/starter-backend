<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package Routes
    |--------------------------------------------------------------------------
    |
    | Disabled: this application registers its own `GET api/report` route
    | (App\Http\Controllers\API\Global\Report\ReportController) so the
    | response goes through successResponse() like every other endpoint.
    | Enable this instead of that route to let the package own the endpoint.
    |
    */
    'routes' => [
        'enabled' => false,
        'prefix' => 'api/report',
        'middleware' => ['api', 'auth:sanctum'],
        'name_prefix' => 'report.',
        'paths' => [
            'report' => '/',
        ],
        'names' => [
            'report' => 'index',
        ],
    ],

    // Optional: set a global namespace for host application report classes.
    // You may also set a page-specific class via report.pages.{page}.class.
    'namespace' => 'App\\Tools\\Report',

    'defaults' => [
        'page' => null,
        'prefer_chart' => 'high_chart',
    ],

    // 'throw' surfaces a broken report component as an exception instead of
    // silently dropping it from the payload. Set to 'ignore' in production if a
    // partial dashboard is preferable to a failed request.
    'component_errors' => 'throw',

    'database' => [
        'disable_mysql_strict_mode' => true,
    ],

    // Translation settings for report labels and role display names
    'translate' => [
        'enabled' => true,
        'trans_file' => 'report',
        'file' => 'report',
    ],

    /*
    |--------------------------------------------------------------------------
    | Card Icons Mapping
    |--------------------------------------------------------------------------
    | Maps a card key to an SVG icon filename (without the .svg extension),
    | served from public/media/report/.
    |
    | Example:
    | 'users_count' => 'users_count',
    */
    'card_icons' => [

    ],

    // Example pages
    'pages' => [
        // Example pages
        'user' => [
            'type' => 'page',
            'report' => [
                'cards' => [
                    'type' => 'card',
                    'size' => [
                        'cols' => '6',
                        'md' => '3',
                        'lg' => '3',
                    ],
                ],
                'registered_users_by_date' => [
                    'type' => 'table',
                    'size' => [
                        'cols' => '12',
                        'md' => '12',
                        'lg' => '12',
                    ],
                ],
                'user_by_gender' => [
                    'type' => 'spline',
                    'size' => [
                        'cols' => '12',
                        'md' => '12',
                        'lg' => '12',
                    ],
                ],
            ],
        ],
    ],
];
