<?php

return [
    'channels' => [
        'push' => [
            'enabled' => true,
            'driver' => 'fcm',
            'required_data' => ['title', 'body'],
            'validations' => [
                'title' => 'max:200',
                'body' => 'max:1000',
            ],
        ],

        'email' => [
            'enabled' => true,
            'required_data' => ['title', 'body'],
            'validations' => [
                'title' => 'max:200',
                'body' => 'max:1000',
            ],
        ],

        'sms' => [
            'enabled' => true,
            'required_data' => ['body'],
            'validations' => [
                'body' => 'max:1000',
            ],
        ],

        'reminder' => [
            'enabled' => true,
            'required_data' => ['title', 'body'],
            'validations' => [
                'title' => 'max:200',
                'body' => 'max:1000',
            ],
        ],

        'calendar' => [
            'enabled' => true,
            'required_data' => ['title', 'body'],
            'validations' => [
                'title' => 'max:200',
                'body' => 'max:1000',
            ],
        ],

        'notification' => [
            'enabled' => true,
            'required_data' => ['title', 'body'],
            'validations' => [
                'title' => 'max:200',
                'body' => 'max:1000',
            ],
        ],
    ],
];
