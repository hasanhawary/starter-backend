<?php

return [

    'filters' => [
        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */
        'users' => [
            'search' => [
                'type' => 'string',
            ],

            /*
             * "advanced": [
             *    {
             *        "key": "created_by",
             *        "value": [1]
             *    }
             * ]
             */
            'advanced' => [
                'created_by' => [
                    'type' => 'integer',
                    'reference' => 'users',
                ],
            ],
        ],

    ],

    'sorting' => [
        'users' => [
            'id',
            'name',
            'email',
            'phone',
            'gender',
        ],
    ],

];
