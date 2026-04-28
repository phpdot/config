<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests\Stubs;

final readonly class HttpConfigStub
{
    /**
     * @param list<string> $trustedProxies
     */
    public function __construct(
        public array $trustedProxies = [],
        public int $trustedHeaders = 0,
        public CookieConfigStub $cookie = new CookieConfigStub(),
    ) {}
}
