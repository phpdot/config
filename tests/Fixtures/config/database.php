<?php

declare(strict_types=1);

return [
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'testdb',
    'username' => 'root',
    'password' => '',
    'debug' => false,

    'staging' => [
        'host' => 'staging-db.internal',
    ],

    'production' => [
        'host' => 'prod-db.internal',
        'port' => 5432,
        'debug' => false,
    ],
];
