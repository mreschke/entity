<?php

return [

    // Default Store
    'default' => 'database',

    // Repository Stores
    'stores' => [

        'database' => [
            [
                'driver' => 'db',
                'connection' => 'fake-repository',
                'entities' => [
                    'address',
                    'client'
                ]
            ],
        ],

        'mongo' => [
            [
                'driver' => 'mongo',
                'host' => 'localhost',
                'port' => 27017,
                'database' => 'fake-repository',
                'entities' => [
                    'address',
                    'client'
                ]
            ],
        ]

    ],

];
