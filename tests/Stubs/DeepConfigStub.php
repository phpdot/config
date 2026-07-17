<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests\Stubs;

final readonly class DeepConfigStub
{
    public function __construct(
        public string $name,
        public HttpConfigStub $http = new HttpConfigStub(),
    ) {}
}
