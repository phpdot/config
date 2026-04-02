<?php

declare(strict_types=1);

return [
    'name' => 'EnvApp',
    'debug' => true,
    'url' => 'http://localhost',

    'development' => [
        'debug' => true,
    ],

    'staging' => [
        'url' => 'https://staging.example.com',
        'debug' => false,
    ],

    'production' => [
        'url' => 'https://example.com',
        'debug' => false,
    ],
];
