<?php

declare(strict_types=1);

return [
    'name' => 'deep-app',
    'http' => [
        'trustedProxies' => ['172.16.0.0/12'],
        'trustedHeaders' => 8,
        'cookie' => [
            'secure'   => true,
            'sameSite' => 'None',
        ],
    ],
];
