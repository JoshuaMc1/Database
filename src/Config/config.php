<?php

return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'database' => sprintf('%s/../database/database.sqlite', __DIR__)
        ],
        'mysql' => [
            'host' => 'localhost',
            'database' => 'database',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
        ],
        'pgsql' => [
            'host' => 'localhost',
            'database' => 'database',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ]
    ]
];
