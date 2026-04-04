<?php

declare(strict_types=1);

return [
    'mailer' => 'smtp',
    'connections' => [
        'mysql' => ['host' => '127.0.0.1', 'port' => 3306],
        'redis' => ['host' => 'localhost', 'port' => 6379],
    ],
    'middleware' => ['auth', 'cors', 'throttle'],
    'features' => [
        'dark_mode' => true,
        'beta' => false,
    ],
    'rate_limit' => 100,
    'empty_list' => [],
];
