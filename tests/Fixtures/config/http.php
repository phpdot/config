<?php

declare(strict_types=1);

return [
    'trustedProxies' => ['10.0.0.0/8'],
    'trustedHeaders' => 31,
    'cookie' => [
        'secure'   => false,
        'httpOnly' => true,
        'sameSite' => 'Strict',
        'path'     => '/admin',
    ],
];
