<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests\Stubs;

final readonly class CookieConfigStub
{
    public function __construct(
        public bool $secure = true,
        public bool $httpOnly = true,
        public string $sameSite = 'Lax',
        public string $path = '/',
    ) {}
}
