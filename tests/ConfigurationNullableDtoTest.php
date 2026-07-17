<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests;

use PHPdot\Config\Configuration;
use PHPdot\Config\Tests\Stubs\NullableConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigurationNullableDtoTest extends TestCase
{
    private Configuration $config;

    protected function setUp(): void
    {
        $this->config = new Configuration(
            path: __DIR__ . '/Fixtures/config',
        );
    }

    #[Test]
    public function dtoPreservesExplicitNullForNullableString(): void
    {
        $dto = $this->config->dto('nullable', NullableConfig::class);

        self::assertNull($dto->cache);
    }

    #[Test]
    public function dtoPreservesExplicitNullForNullableInt(): void
    {
        $dto = $this->config->dto('nullable', NullableConfig::class);

        self::assertNull($dto->port);
    }

    #[Test]
    public function dtoPreservesExplicitNullForNullableFloat(): void
    {
        $dto = $this->config->dto('nullable', NullableConfig::class);

        self::assertNull($dto->ratio);
    }

    #[Test]
    public function dtoPreservesExplicitNullForNullableBool(): void
    {
        $dto = $this->config->dto('nullable', NullableConfig::class);

        self::assertNull($dto->debug);
    }
}
