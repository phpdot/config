<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests\Stubs;

final readonly class DatabaseConfig
{
    public function __construct(
        public string $host,
        public int $port,
        public string $name,
        public string $username,
        public string $password = '',
        public bool $debug = false,
    ) {}
}
