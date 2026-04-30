<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests\Stubs;

final readonly class NullableConfig
{
    public function __construct(
        public ?string $cache = null,
        public ?int $port = null,
        public ?float $ratio = null,
        public ?bool $debug = null,
    ) {}
}
