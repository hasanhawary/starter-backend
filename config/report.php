<?php

return [
    // Optional: set a global namespace for your app report classes
    'namespace' => 'App\\Tools\\Report',

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
