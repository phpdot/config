<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests\Stubs;

final readonly class NullableConfig
{
    public function __construct(
        public null|string $cache = null,
        public null|int $port = null,
        public null|float $ratio = null,
        public null|bool $debug = null,
    ) {}
}
