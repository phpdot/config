<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests\Stubs;

final readonly class MysqlConfigStub
{
    public function __construct(
        public string $host = 'localhost',
        public int $port = 3306,
        public string $charset = 'utf8mb4',
    ) {}
}
